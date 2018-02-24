#include "Configuration.h"
#include "StateMachineCounter.h"
#include "BEDataPacket.h"
#include "RX8025.h"
#include "R8025.h"
#include <TimerOne.h>
#include <avr/sleep.h>
#include "Interrupt.h"

  extern void sensor_interupt_ISR() {} //sensor interupt function
  extern void clock_interupt_ISR() {}  //rtc interupt function

Interrupt::Interrupt()
{
}

/*
* Initiate the object  
*/
void Interrupt::init()
{

}

/*
* set the microcontroller to sleep. 
*/ 
void Interrupt::sleep()
{
  RX8025.getRtcTime(sleep_timestamp_ptr);
  Timer1.detachInterrupt();
  set_sleep_mode(SLEEP_MODE_PWR_DOWN);
  if(WRITE_INTERRUPT_INFO) Serial.print("\nProcessor going to sleep.\n");
  if(USE_LED)  digitalWrite(LED_port,LOW);
  digitalWrite(latch_control_port,HIGH);
  RTC.refreshINTA();
  sleep_enable();
  attachInterrupt(interrupt0, clock_interupt_ISR, LOW); 
  if(! ROUTER) attachInterrupt(interrupt1, sensor_interupt_ISR, RISING);
  // if the sensor interrupt goes off between attaching the interrupt and sleep mode finishing, that interrupt is locked out until the RTC interrupt occurs.
  // The same does not apply to RTC interrupt occuring between attaching the interrupt and sleep mode finishing.
  sleep_mode();
  
  // Processor is sleeping //
  
  sleep_disable();
  detachInterrupt(interrupt0);
  if(! ROUTER) detachInterrupt(interrupt1);
  if(WRITE_INTERRUPT_INFO)  Serial.print("\nProcessor waking up.\n\n");
  if(USE_LED)  digitalWrite(LED_port,HIGH);
  
  sleep_SMC();
  sleep_packet_handler();
  sleep_packet_counter();
  digitalWrite(latch_control_port,LOW); // read sensors
}

/*
* This restarts the SMC to poll the sensors again
*/
void Interrupt::sleep_SMC()
{
  DataCounter.timer_init();
  sleep_processor = 0;
  sleep_counter = 0;
  collect_data_point_counter = 0;
  DataCounter.reset_all_states();
  
  // see who woke up the processor
  bool current_motion = digitalRead(motion_sensor1_port);
  bool current_door = digitalRead(door_sensor1_port);
  if(current_motion == logic_high || current_door == logic_high) // Sensors woke up processor
    DataCounter.counter_state();
}

/*
* Get packet timestamp - if it was from yesterday, send packet
* This is to make sure that the offset in BEDataPacket is not overflow.
*/
void Interrupt::sleep_packet_handler()
{
  packet_timestamp_ptr = beDataPkt.getSeqTimestamp().getTS();
  RX8025.getRtcTime(current_timestamp_ptr);
  if(current_timestamp_ptr[4] != packet_timestamp_ptr[4] || (current_timestamp_ptr[2] - packet_timestamp_ptr[2] > 12))
  {
    // send data packet from yesterday or if more than 12 hours old
    old_packet = 1;
  }
}

/*
* find out how long you have been asleep and add it to counters
*/
void Interrupt::sleep_packet_counter()
{
  int sleep_time = ( ((current_timestamp_ptr[2] - sleep_timestamp_ptr[2]) * 60 * 60) + 
    ((current_timestamp_ptr[1] - sleep_timestamp_ptr[1]) * 60) + 
    ((current_timestamp_ptr[0] - sleep_timestamp_ptr[0])) ); //caculating sleep time 
  
   send_temp_packet_counter += (sleep_time * TIME_DIVISOR);
   send_data_packet_counter += (sleep_time * TIME_DIVISOR);
  
}
