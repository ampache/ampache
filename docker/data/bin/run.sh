#!/bin/bash

# Start Supervisor to manage all the processes
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
