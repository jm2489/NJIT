# Steps to setup RabbitMQ and have it run persistently on boot by Judrianne Mahigne

$ This is assuming your machine has been properly configured by the tutorial provided by Dr. Kehoe

1. Copy all files to your local machine
2. Open testRabbitMQ.ini and edit the file to match the config of the RabbitMQ server.
    - Change BROKER_HOST to the IP address of the RabbitMQ server if not running on your local machine.
    - Change USER and PASSWORD to match the RabbitMQ login credentials
3. Open testRabbitMQServer.php and edit the doLogin() function.
    - Change the $dbLogin, $dbUsername, and $dbPassword to the MySQL server you are connecting to
4. Open and edit the testRabbitMQServer.service
    - If not already obvious, edit the ExecStart, User, and Group fields
    - ExecStart is the ABSOLUTE path of the testRabbitMQServer.php is located.
        You should only have to edit the path leading to the php script. NO, I WILL NOT HELP YOU. FIGURE IT OUT!
    - User and Group is the user and group of the testRabbitMQServer.php
        If you really don't know how to find the user and group of a file it is literally the information you 
        get when you enter the command "ls -l" listing the files in a directory
5. Copy the testRabbitMQServer.service file to the systemd directory.
    - The command to do that is "sudo cp testRabbitMQServer.service /etc/systemd/system/testRabbitMQServer.service"
6. Reload systemd and start the service.
    - sudo systemctl daemon-Reload
    - sudo systemctl start testRabbitMQServer
7. The testRabbitMQServer.php now runs as a service and will automatically start up on boot.