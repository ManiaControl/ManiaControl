*******************************************************
*																		*
*		ManiaControl - ManiaPlanet Server Controller		*
*				Written by steeffeen & kremsy					*
*																		*
*		Website:														*
*				ManiaControl.com									*
*																		*
*		Contact:														*
*				steff@maniacontrol.com,							*
*				lukas@maniacontrol.com							*
*																		*
*******************************************************

SETUP:

1.	Copy all Files into the desired Directory.

2.	Configure the needed Settings:

	2.1	Open the File 'configs/server.xml'.
			Enter Your ManiaPlanet-Server Information.
			(The needed Settings are defined in the file 'dedicated_cfg.txt' of Your Game-Server.)

	2.2	Open the File 'configs/database.xml'
			Enter the Information about Your MySQL-Server, -User and -Database. 

	2.3	Open the File 'configs/authentication.xml'.
			Add as many Player-Logins of Master-Administrators as You wish.

3.	Run the Controller with the Shell Script 'ManiaControl.sh' (on UNIX) or with the Batch File 'ManiaControl.bat' (on Windows).

4.	Enjoy!


INFORMATION:

- ManiaControl is only tested on UNIX Machines.
	- Even though it might run properly on Windows we can't promise it will work all Cases.
	- Furthermore we can't promise that there won't be a Feature in the Future that makes it impossible to run ManiaControl on Windows.
	- In order to run ManiaControl on Windows you have to alter the File ManiaControl.bat and enter the Path to your php.exe!

- Tests were performed using PHP Version 5.4!
- If You notice Problems with other Versions please let us know.

- Please report bugs by writing a Mail to bugs@maniacontrol.com!
