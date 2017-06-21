ManiaControl
============

The newly designed and easy to use ManiaPlanet Server Controller.

https://www.maniacontrol.com


## SETUP:

1.	Copy all files into the desired directory.

2.	Configure the needed settings in the 'configs/server.xml' file:

	2.1	Enter Your ManiaPlanet-server information.
			(The needed settings are defined in the file 'dedicated_cfg.txt' of your game server.)

	2.2	Enter the Information about your MySQL server, user and database.

	2.3	Add as many player logins of master-administrators as you wish.

3.	Run the controller with the shell script 'ManiaControl.sh' (on UNIX) or with the batch file 'ManiaControl.bat' (on Windows).

4.	Enjoy!


## WINDOWS:

- ManiaControl is mainly tested on UNIX machines.
- Even though it might run properly on Windows we can't promise it will work in all cases.
- In order to run ManiaControl on Windows you have to alter the file 'ManiaControl.bat' and enter the path to your php.exe!


## REQUIREMENTS:
- MySQL Database
- PHP 5.4+
- Needed extensions (on ManiaControl startup you will see if you have them installed and activated):
	- php_mysqli
	- php_curl
	- php_xmlrpc (TM only, recommended for SM)
	- php_zlib
	- php_zip
	- php_mbstring
	
### How to report bugs or request features?:
- Write a mail to bugs(at)maniacontrol(dot)com
- Open an issue on GitHub.com/ManiaControl/ManiaControl
- Post in the ManiaPlanet Forum
