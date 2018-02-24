#include <Wire.h>
#include <math.h>
#include <XBee.h>
#include <TimerOne.h>

#include "Battery.h"
#include "RX8025.h"
#include "tmp102.h"

#include "Configuration.h"
#include "StateMachineCounter.h"
#include "BEDataPacket.h"
#include "ProtocolBase.h"
#include "TempVoltagePacket.h"
#include "Interrupt.h"

extern void sleep();
unsigned int counter;  // packet counter

// data packet variables
int collect_data_point_counter; // counter for creating a data point
boolean send_data_packet;  // flag to send a data packet - only set within block that prepares the packet
int send_data_packet_counter;  // counter for sending data packets
boolean initial_data_point;  // need to know which data point is first for offset purposes
boolean old_packet; // use to send yesterday's packets

// temp voltage variables
float convertedtemp;
int tmp102_val; 
unsigned int bat_read;
float bat_voltage;
unsigned char charge_status;
int send_temp_packet_counter;
boolean send_temp_packet;  // flag to send a temp packet - only set within block that prepares the packet

//Configuration variables
//unsigned char KEEP_PROCESSOR_AWAKE, USE_LED;
//unsigned char DATA_POINT_INTERVAL, SLEEP_WAIT_TIME, SEND_DATA_PACKET_INTERVAL, SEND_TEMP_PACKET_INTERVAL;

// sleep and interrupt variables
int XBee_wakeup_counter;  // counter for waiting until you can send a packet after waking up XBee
boolean XBee_wakeup; // set this flag when you wake up the XBee and leave set until XBee goes to sleep
boolean sleep_processor;  // set to 1 when you want to sleep Arduino
int sleep_counter; // counter for putting processor to sleep

// create the XBee object
XBee xbee;

// create packets
TempVoltagePacket tempPkt;
BEDataPacket beDataPkt;

// SH + SL Address of receiving XBee -- 0x00, 0x00 === Send to coordinator
XBeeAddress64 addr64(0x00, 0x00);
ZBTxRequest zbTxTemp(addr64, (uint8_t *)&tempPkt, sizeof(tempPkt));
ZBTxRequest zbTxBEData(addr64, (uint8_t *)&beDataPkt, sizeof(beDataPkt));

// Data counter object to collect data
SMC DataCounter;

// Interrupt control
Interrupt control_interrupt;

// Clock
RX RTclock;


void setup()
{
  counter = 0x10;
  XBee_wakeup = 0;
  XBee_wakeup_counter = 0;
  xbee.begin(9600);
  
  Battery_init();
  RX8025.init();
  tmp102_init();
  DataCounter.SMC_init();
  

  collect_data_point_counter = 0;
  send_data_packet_counter = 0;
  send_temp_packet_counter = 0;
  initial_data_point = 1;
  old_packet = 0;
  
  pinMode(XBee_sleep_port, OUTPUT);
 
  if(TURN_ON_SERIAL_PORT) Serial.begin(9600);
  
  // Set the RTC
  unsigned char clock[7] = {0x00,0x00,0x00,0x00,0x00,0x00,0x0000};  //second, minute, hour, day of week, date, month, year (BCD format)
  RTclock.setRtcTime(clock);
  
  if(TURN_ON_SERIAL_PORT) Serial.print("debug");
  if(TURN_ON_SERIAL_PORT) Serial.print(KEEP_PROCESSOR_AWAKE,DEC);
  if(TURN_ON_SERIAL_PORT) Serial.print(USE_LED,DEC);
    
}

void loop()
{
  // increment one-second counters
  if(DataCounter.check_second_change_flag() == 1)  // DataCounter.change_flag == 1 after each timer interrupt
  {
   collect_data_point_counter++;
   send_data_packet_counter++;
   send_temp_packet_counter++;
   if(! ROUTER) DataCounter.counter_state(); // read sensor inputs, increment state counters
   if(send_data_packet || send_temp_packet) XBee_wakeup_counter++;
   DataCounter.reset_flag(); // DataCounter.change_flag = 0
  }


  // save data to data point
  if((collect_data_point_counter/TIME_DIVISOR) >= (DATA_POINT_INTERVAL*60) && ! ROUTER)  
  {    
    if(initial_data_point)  // first data point in packet
    {
      RX8025.getRtcTime(beDataPkt.getTimestamp().getTS());
      RX8025.getRtcTime(beDataPkt.getSeqTimestamp().getTS());
    }
  
    BEDataPoint dp;
    
    dp.offset = DataCounter.get_offset();
    dp.timeExiting = DataCounter.get_Exiting();
    dp.timeEntering = DataCounter.get_Entering();
    dp.timeMotionNoDoor = DataCounter.get_MotionNoDoor();
    dp.timeDoorNoMotion = DataCounter.get_NoMotionDoor();
    dp.timeNoMotion = DataCounter.get_NoMotionNoDoor();
    
    beDataPkt.addDatapoint(dp);
    collect_data_point_counter = 0;
    DataCounter.reset_all_states();
    initial_data_point = 0;
    if(WRITE_SERIAL_DATAPOINTS)  DataCounter.print_datapoint(dp);
  }
  
  
  // prepare to send data packet
  if((((double)send_data_packet_counter/TIME_DIVISOR/60 >= SEND_DATA_PACKET_INTERVAL) || beDataPkt.isFull() || 
    old_packet) && ! beDataPkt.isEmpty() && ! send_data_packet && USE_DATA_PACKET && ! ROUTER)
  {
    if(! XBee_wakeup)
    {
      digitalWrite(XBee_sleep_port,LOW); 
      XBee_wakeup = 1;
    }
    send_data_packet = 1;
    send_data_packet_counter = 0; 
  }
  
  // prepare to send temp packet
  if((((double)send_temp_packet_counter/TIME_DIVISOR/60 >= SEND_TEMP_PACKET_INTERVAL)  || old_packet) 
    && ! send_temp_packet && USE_TEMP_VOLTAGE_PACKET)
  {
    getTemp102();
    RX8025.getRtcTime(tempPkt.getTimestamp().getTS());
    tempPkt.setTemp(convertedtemp);       
    tempPkt.setBatteryVoltage(bat_voltage); 
    
    if(! XBee_wakeup)
    {
      digitalWrite(XBee_sleep_port,LOW); 
      XBee_wakeup = 1;
    }
    send_temp_packet = 1;
    send_temp_packet_counter = 0;
    old_packet = 0;
  }
  
  // send packets
  if((send_data_packet || send_temp_packet) && ((double)XBee_wakeup_counter/TIME_DIVISOR >= XBEE_WAKEUP_TIME))
  {
    // send data packet
    if(send_data_packet)
    {
      beDataPkt.setCounter(counter++);
      if(WRITE_SERIAL_DATAPOINTS)  Serial.print("---------- Data Packet ---------\n");
      /* To do
       * scroll through data points to send multiple packets - use data point pointer to keep track
       */
      xbee.send(zbTxBEData);
      beDataPkt.reset();
      if(WRITE_SERIAL_DATAPOINTS)  Serial.print("\n--------------------------------\n\n\n");
    
      send_data_packet = 0;
      initial_data_point = 1; // next data point is first in packet
    }
    
    // send temp voltage packet
    if(send_temp_packet)
    {
      tempPkt.setCounter(counter++);
      if(WRITE_SERIAL_DATAPOINTS)  Serial.print("\n\n----- Temp Voltage Packet ------\n");
      xbee.send(zbTxTemp);  // send temp voltage packet
      if(WRITE_SERIAL_DATAPOINTS)  Serial.print("\n--------------------------------\n");
      
      send_temp_packet = 0;
    }
    
    
    // To-do: check if there is an incoming packet
    // check xbee class functions for incoming data

    
    // put XBee to sleep
    delay(5);  // wait 5 msec after XBee sends data
    digitalWrite(XBee_sleep_port,HIGH);
    XBee_wakeup_counter = 0;
    XBee_wakeup = 0;
    
  }
  
  
  if(ROUTER)
    sleep_processor = 1;
      
  // put processor to sleep
  if(send_data_packet == 0 && send_temp_packet == 0 && sleep_processor == 1 && ! KEEP_PROCESSOR_AWAKE)
      control_interrupt.sleep();
  
}


