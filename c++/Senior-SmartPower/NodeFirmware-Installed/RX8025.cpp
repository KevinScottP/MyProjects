/*
 * RX8025.cpp - Interface a RTC to an AVR via i2c
 * Version 0.1 - http://www.timzaman.com/
 * Copyright (c) 2011 Tim Zaman
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS
 * ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED
 * TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE FOUNDATION OR CONTRIBUTORS
 * BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */
 
#include "RX8025.h"
#include "WProgram.h"
#include <Wire.h>

#define RX8025_SEC      0
#define RX8025_MIN      1
#define RX8025_HR       2
#define RX8025_WEEK     3
#define RX8025_DATE     4
#define RX8025_MTH      5
#define RX8025_YR       6
#define RX8025_Doffset  7
#define RX8025_AW_MIN   8
#define RX8025_AW_HR    9
#define RX8025_AW_WEEK  10
#define RX8025_AD_MIN   11
#define RX8025_AD_HR    12
#define RX8025_CTL1     14
#define RX8025_CTL2     15
#define RX8025_address  0x32



/* PUBLIC METHODS */

RX::RX()
{
}

unsigned char RX8025_Control[2]={0x20,0x00};
unsigned char RX8025_time[7]={0x00,0x52,0x13,0x03,0x22,0x11,0x11};  //second, minute, hour, day-of-week, date, month, year, BCD format}


void RX::init()
{
  Wire.begin();
  Wire.beginTransmission(RX8025_address);//clear power on reset flag, set to 24hr format
  Wire.send(0xe0);
  for(unsigned char i=0; i<2; i++)
  {
    Wire.send(RX8025_Control[i]);
  }
  Wire.endTransmission();
}

void RX::setRtcTime(unsigned char RX8025_time[7])
{
  Wire.beginTransmission(RX8025_address);
  Wire.send(0x00);
  for(unsigned char i=0; i<7; i++)
  {
    Wire.send(RX8025_time[i]);
  }
  Wire.endTransmission();
}

void RX::getRtcTime(unsigned char RX8025_buffer[7])
{
  Wire.beginTransmission(RX8025_address);
  Wire.send(0x00);
  Wire.endTransmission();//
  Wire.requestFrom(RX8025_address,8);
  RX8025_buffer[0]= Wire.receive();//not used

  RX8025_buffer[0]= Wire.receive();
  RX8025_buffer[1]= Wire.receive();
  RX8025_buffer[2]= Wire.receive();
  RX8025_buffer[3]= Wire.receive();
  RX8025_buffer[4]= Wire.receive();
  RX8025_buffer[5]= Wire.receive();
  RX8025_buffer[6]= Wire.receive();

  RX8025_buffer[6] = bcd2bin(RX8025_buffer[6]&0xff);  // year
  RX8025_buffer[5] = bcd2bin(RX8025_buffer[5]&0x1f);  // month
  RX8025_buffer[4] = bcd2bin(RX8025_buffer[4]&0x3f);  // day
  RX8025_buffer[3] = bcd2bin(RX8025_buffer[3]&0x07);  // day-of-week
  RX8025_buffer[2] = bcd2bin(RX8025_buffer[2]&0x3f);  // hour
  RX8025_buffer[1] = bcd2bin(RX8025_buffer[1]&0x7f);  // minute
  RX8025_buffer[0] = bcd2bin(RX8025_buffer[0]&0x7f);   // second
}

//===============================================
uint8_t RX::bcd2bin (uint8_t val) 
{ 
  return val - 6 * (val >> 4); 
}

uint8_t RX::bin2bcd (uint8_t val) 
{ 
  return val + 6 * (val / 10); 
}


// Set the default object
RX RX8025 = RX();






