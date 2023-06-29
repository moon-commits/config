#!/bin/sh
ulimit -n 65535
while true; do
	#启动一个循环，定时检查进程是否存在
	server=`ps aux | grep memcached | grep -v grep  | wc -l`
	if [ "$server" -lt "1" ]; then
	    #如果不存在就重新启动
	    echo "重启 memcached 服务：`date '+%Y-%m-%d %H:%M:%S'`"
	    #启动后沉睡10s
		/usr/bin/memcached -d start -m 200 -u root -p 11211 -U 0 -l 127.0.0.1 -c 4096 -P /tmp/memcached.pid &
		sleep 2
	fi			
		
	server=`ps aux | grep mem_remote_update | grep -v grep  | wc -l`
	if [ "$server" -lt "1" ]; then
	    #如果不存在就重新启动
	    echo "重启 mem_remote_update 服务：`date '+%Y-%m-%d %H:%M:%S'`"
	    #启动后沉睡10s
		/usr/bin/php /root/shadowsocks-2.8.2/mem_remote_update.php >> /root/shadowsocks-2.8.2/sql.log 2>&1 &
		sleep 2
	fi	
		
	server=`ps aux | grep server.py | grep -v grep  | wc -l`
	if [ "$server" -lt "4" ]; then
	    #如果不存在就重新启动
	    echo "重启 python 服务：`date '+%Y-%m-%d %H:%M:%S'`"
	    #启动后沉睡10s	`ps -ef | grep server.py | grep -v grep | awk 'NR==1{print $2}' | xargs kill`
			server=`ps -ef | grep server.py | grep -v grep | awk 'NR==1{print $2}'`
			if [ $server ]; then
				/usr/bin/kill $server	
				sleep 5
			fi
			cd /root/shadowsocks-2.8.2/shadowsocks/ && python server.py >> /root/shadowsocks-2.8.2/ss.log 2>&1 &
	    sleep 10
	fi
	sleep 5
done
