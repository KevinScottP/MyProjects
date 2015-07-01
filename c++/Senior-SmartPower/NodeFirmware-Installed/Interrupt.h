
#ifndef Interrupt_h
#define Interrupt_h

#include "Configuration.h"
#include "StateMachineCounter.h"
#include "BEDataPacket.h"
#include "RX8025.h"
#include "R8025.h"
#include <TimerOne.h>
#include <avr/sleep.h>

#define interrupt0 0
#define interrupt1 1

  //attachInterrupt function looks for function in the global namespace.

class Interrupt
{
	public:
	Interrupt();
	void sleep();
        void init();

        
        protected:
        unsigned char sleep_timestamp_ptr [7];
        void sleep_packet_counter();
        void sleep_packet_handler();
        void sleep_SMC();

};

extern SMC DataCounter;
extern int sleep_counter;
extern boolean sleep_processor;
extern BEDataPacket beDataPkt;
extern boolean old_packet;
extern unsigned char * packet_timestamp_ptr;
extern unsigned char current_timestamp_ptr [7];
extern int collect_data_point_counter;
extern int send_data_packet_counter;
extern int send_temp_packet_counter;
extern R8025 RTC;
extern boolean RTC_interrupt;

#endif
