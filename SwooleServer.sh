#!/bin/bash
PATH=/usr/local/php/bin:/opt/someApp/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

if [ $1 == "start" ]
  then
  echo "do start"
  php think swooleServer -m start
  elif [ $1 == "stop" ]
  then
  echo "do stop"
  php think swooleServer -m stop
  ps -ef | grep swooleServer | grep -v grep | awk '{print $2}' | xargs kill -9
  else
  echo "Please make sure the positon variable is start or stop."
fi
