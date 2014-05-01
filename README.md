ManiaControl
============

The newly designed and easy to use ManiaPlanet Server Controller.

http://www.maniacontrol.com


SETUP:

1.	Copy all Files into the desired Directory.

2.	Configure the needed Settings in the 'configs/server.xml' file:

	2.1	Enter Your ManiaPlanet-Server Information.
			(The needed Settings are defined in the file 'dedicated_cfg.txt' of Your Game-Server.)

	2.2	Enter the Information about Your MySQL-Server, -User and -Database.

	2.3	Add as many Player-Logins of Master-Administrators as You wish.

3.	Run the Controller with the Shell Script 'ManiaControl.sh' (on UNIX) or with the Batch File 'ManiaControl.bat' (on Windows).

4.	Enjoy!


INFORMATION:

- ManiaControl is mainly tested on UNIX Machines.
	- Even though it might run properly on Windows we can't promise it will work all Cases.
	- In order to run ManiaControl on Windows you have to alter the File ManiaControl.bat and enter the Path to your php.exe!

- Tests were performed using PHP Version 5.4!
- In the current nightly release there is no 5.3 support (it should come soon)
- If You notice Problems with other Versions please let us know.
- You need to activate the php extension php_curl and php_mysqli
- for trackmania you need also activate the php_xmlrpc extension



- Please report bugs by writing a Mail to bugs@maniacontrol.com!
