#include "WProgram.h"
#include "BEDataPacket.h"

#ifndef StateMachineCounter_h
#define StateMachineCounter_h
#define logic_high 1
#define logic_low 0
#define interrupt0 0
#define interrupt1 1

class SMC
{
	public:
      	  SMC();
          void set_time(int c_sec,int c_min,int c_hr);
          void counter_state();
          void SMC_init();
          unsigned char get_NoMotionNoDoor();
          unsigned char get_MotionNoDoor();
          unsigned char get_NoMotionDoor();
          unsigned char get_Exiting();
          unsigned char get_Entering();
          boolean check_second_change_flag();
          void reset_flag();
          short unsigned int get_offset();
          void reset_offset();
          void reset_all_states();
          void timer_init();
          void print_datapoint(BEDataPoint dp);

	private:
          unsigned short NoMotionNoDoor, MotionNoDoor, NoMotionDoor, Exiting, Entering;
          static void current_time();
	  void set_local_varables();
          boolean debounce(char port);
          

};

extern int collect_data_point_counter;
extern int send_temp_packet_counter;
extern int send_data_packet_counter;
extern int sleep_counter;
extern boolean sleep_processor;
extern BEDataPacket beDataPkt;
extern boolean old_packet;
extern boolean initial_data_point;

#endif
