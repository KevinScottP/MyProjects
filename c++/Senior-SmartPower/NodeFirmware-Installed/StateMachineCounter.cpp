/*
*Author: Kevin Premo, et al.
*Program: Motion interupt 
*/	

#include "Configuration.h"
#include "StateMachineCounter.h"
#include "WProgram.h"
#include "BEDataPacket.h"
#include "RX8025.h"
#include "R8025.h"
#include <TimerOne.h>

R8025 RTC;
static bool change_flag = 0;
unsigned char * packet_timestamp_ptr;
unsigned char current_timestamp_ptr [7];

SMC::SMC()
{}

/*
* sets up the controller
*/
void SMC::SMC_init()
{ 
 pinMode(motion_sensor1_port,INPUT);
 pinMode(door_sensor1_port,INPUT);
 pinMode(sensor_interrupt_port,INPUT);

 digitalWrite(clock_interrupt_port,HIGH);//interrupt code
 pinMode(clock_interrupt_port,INPUT);//interrupt code
 RTC.begin(); //interrupt code
 RTC.enableINTA_Interrupts(RTC_INTERRUPT); //interrupt at  EverySecond, EveryMinute, EveryHour or EveryMonth
 
 pinMode(latch_control_port,OUTPUT); //interrupt code
 digitalWrite(latch_control_port,HIGH); //interrupt code

 if(USE_LED) 
 {
   pinMode(LED_port,OUTPUT);
   digitalWrite(LED_port,HIGH); 
 }

 // initialize timer interrupt (smc timer this is to give a simi arcrate sampling of the sensors)
 Timer1.initialize(1000000/TIME_DIVISOR);
 Timer1.attachInterrupt(&current_time,1000000/TIME_DIVISOR);
 
}

void SMC::timer_init()
{
 Timer1.attachInterrupt(&current_time,1000000/TIME_DIVISOR);
}

/*
* this happends every second to update time
*/
void SMC::current_time()
{
 change_flag = 1;
}

boolean SMC::check_second_change_flag()
{
 return change_flag;
}

void SMC::reset_flag()
{
 change_flag = 0;
}

/*
* This is design to take 25ms, the motion signal with all set to low for 50ms is defined as a low signal. Anything else is considered high.
* This function is to minimise the bouncing signal miss read. The sensor has a 500mS gate delay (from movement to digital high) and 10ms min signal length (if triggered)
* How long the input is high is unpredictable, but between 10ms to several seconds. (large motion sensor,pg 33, Q15)
*/
boolean SMC::debounce(char port)
{
  boolean tmp_port = 0;
  for(char debounce_time = 0; debounce_time != 5; debounce_time++) 
  {
    delay(5);
    tmp_port = digitalRead(port);
   if(logic_high == tmp_port)
     return tmp_port;
  }
  return tmp_port;
}

/*
* This caculates what state the door and motion sensor are in and increments the counter for the respected state.
*/
void SMC::counter_state()
{
  boolean current_motion;
  boolean current_door;
  static int state;
  current_motion = debounce(motion_sensor1_port);
  current_door = digitalRead(door_sensor1_port);
  
  if(state == 3 && current_motion == logic_high && current_door == logic_high)
  {
   if(WRITE_SERIAL_SENSORS && collect_data_point_counter%TIME_DIVISOR == 0) Serial.print("Exiting and motion \n");
   Exiting++;
   state = 3;
   set_local_varables();
  }
  else if(state == 4 && current_motion == logic_high && current_door == logic_high)
  {
   if(WRITE_SERIAL_SENSORS && collect_data_point_counter%TIME_DIVISOR == 0) Serial.print("Entering and motion\n");
   Entering++;
   state = 4;
   set_local_varables();
  }
  else if(current_motion == logic_low && current_door == logic_low)
  {
   if(WRITE_SERIAL_SENSORS && collect_data_point_counter%TIME_DIVISOR == 0) Serial.print("no motion, no door \n");
   NoMotionNoDoor++;
   state = 0;
   sleep_counter++;
   digitalWrite(latch_control_port,HIGH); // latch first sensor detected (?)
  }
  else if(current_motion == logic_high && current_door == logic_low)
  {
   if(WRITE_SERIAL_SENSORS && collect_data_point_counter%TIME_DIVISOR == 0) Serial.print("motion, no door \n");
   MotionNoDoor++;
   state = 3;
   set_local_varables();
  }
  else if(current_motion == logic_low && current_door == logic_high)
  {
   if(WRITE_SERIAL_SENSORS && collect_data_point_counter%TIME_DIVISOR == 0) Serial.print("no motion, door \n");
   NoMotionDoor++;
   state = 4;
   set_local_varables();
  }
  else // motion and door at the same time - didn't see which was first - just count motion
  {    
    if(WRITE_SERIAL_SENSORS && collect_data_point_counter%TIME_DIVISOR == 0) Serial.print("Error - motion and door - didn't see which was first. \n"); 
    state = 0;
    set_local_varables();
  } 
  
  // check if it is time to go to sleep
  if((sleep_counter/TIME_DIVISOR) >= SLEEP_WAIT_TIME)
    sleep_processor = 1;
}

void SMC::set_local_varables()
{
    sleep_counter = 0;
    sleep_processor = 0;
    digitalWrite(latch_control_port,LOW); // pass inputs through
}

/*
* gets the NoMotionNoDoor value and sets it to 0
*/
unsigned char SMC::get_NoMotionNoDoor() 
{
 return round((double)NoMotionNoDoor/TIME_DIVISOR);
}

/*
* gets the MotionNoDoor value and sets it to 0
*/
unsigned char SMC::get_MotionNoDoor() 
{
 return round((double)MotionNoDoor/TIME_DIVISOR);
}

/*
* gets the NoMotionDoor value and sets it to 0
*/
unsigned char SMC::get_NoMotionDoor() 
{
 return round((double)NoMotionDoor/TIME_DIVISOR);
}

/*
* gets the Exiting value and sets it to 0
*/
unsigned char SMC::get_Exiting() 
{
 return round((double)Exiting/TIME_DIVISOR);
}

/*
* gets the Entering value and sets it to 0
*/
unsigned char SMC::get_Entering() 
{
 return round((double)Entering/TIME_DIVISOR);
}

/*
* return time offset - time difference between packet time and datapoint time
*/
short unsigned int SMC::get_offset()
{
  if(initial_data_point == 1)
    return 0;
  else
  {
    packet_timestamp_ptr = beDataPkt.getSeqTimestamp().getTS();
    RX8025.getRtcTime(current_timestamp_ptr);

    return ( ((current_timestamp_ptr[2] - packet_timestamp_ptr[2]) * 60 * 60) + 
    ((current_timestamp_ptr[1] - packet_timestamp_ptr[1]) * 60) + 
    ((current_timestamp_ptr[0] - packet_timestamp_ptr[0])) ); // find out how long you have been asleep and add it to counters
  }
}

/*
* Reset all of the states: NoMotionNoDoor, MotionNoDoor, NoMotionDoor, Exiting, Entering.
*/
void SMC::reset_all_states()
{
  NoMotionNoDoor = MotionNoDoor = NoMotionDoor = Exiting = Entering = 0;
}


/*
* Output the data point that when into a packet, for testing only.
*/
void SMC::print_datapoint(BEDataPoint dp)
{
  packet_timestamp_ptr = beDataPkt.getSeqTimestamp().getTS();
  RX8025.getRtcTime(current_timestamp_ptr);
  
  Serial.print("\n\n---------- Data Point ----------");
  Serial.print("\nPacket timestamp = ");
  for(int i=6; i>=0; i--)
  {
    Serial.print((unsigned int) packet_timestamp_ptr[i]);
    Serial.print(":");
  }
  Serial.print("\nCurrent timestamp = ");
  for(int i=6; i>=0; i--)
  {
    Serial.print((unsigned int) current_timestamp_ptr[i]);
    Serial.print(":");
  }
  Serial.print("\nTime offset = ");
  Serial.print(dp.offset);
  Serial.print("\nTime exiting = ");
  Serial.print((unsigned int)dp.timeExiting);
  Serial.print("\nTime entering = ");
  Serial.print((unsigned int)dp.timeEntering);
  Serial.print("\nTime with motion but no door = ");
  Serial.print((unsigned int)dp.timeMotionNoDoor);
  Serial.print("\nTime with door but no motion = ");
  Serial.print((unsigned int)dp.timeDoorNoMotion);
  Serial.print("\nTime with no motion and no door = ");
  Serial.print((unsigned int)dp.timeNoMotion);
  Serial.print("\n--------------------------------\n\n\n");
}

