/*
*
* For End Node:
*    ROUTER = 0
*
* For router:
*    ROUTER = 1
*
*/

#ifndef Configuration_h
#define Configuration_h


// Router or End Device
#define ROUTER 0 // Set to '1' for router, '0' for end device


// Other configuration
#define DATA_POINT_INTERVAL 1 // interval for collecting data for one data point (in minutes)
#define SLEEP_WAIT_TIME 59 // how long to wait with no sensor results before going to sleep (in seconds) - set one second less than DATA_POINT_INTERVAL (which is in minutes)
#define SEND_DATA_PACKET_INTERVAL 1//5 // how often to send a data packet (in minutes)
#define SEND_TEMP_PACKET_INTERVAL 5//60 // how often to send temp packet (in minutes)
#define RTC_INTERRUPT EveryHour  //interrupt at  EverySecond, EveryMinute, EveryHour or EveryMonth
#define TIME_DIVISOR 10  // how many times per second you poll the sensors - must be less than 20 to allow time to debounce (?)
#define XBEE_WAKEUP_TIME 12 // how long it takes the XBee radio to wakeup (in seconds)

// Ports
#define clock_interrupt_port 2
#define sensor_interrupt_port 3
#define LED_port 8
#define XBee_sleep_port 9
#define latch_control_port 10
#define door_sensor1_port 11
#define motion_sensor1_port 12


// Debug variables - normally '0'
#define TURN_ON_SERIAL_PORT 1
#define WRITE_SERIAL_SENSORS 1
#define WRITE_SERIAL_DATAPOINTS 1
#define WRITE_INTERRUPT_INFO 1
#define KEEP_PROCESSOR_AWAKE 0
#define USE_LED 1



// Debug variables - normally '1'
#define USE_TEMP_VOLTAGE_PACKET 1
#define USE_DATA_PACKET 1

#endif
