*******************************************************
*																		*
*		ManiaControl - ManiaPlanet Server Controller		*
*				Written by steeffeen & kremsy					*
*																		*
*		Contact:														*
*				steff@maniacontrol.com,							*
*				lukas@maniacontrol.com							*
*																		*
*******************************************************

SETUP:

1.	Copy all files into your desired directory.

2.	Configure the needed settings:

	2.1	Open the file 'configs/server.xml'.
			Enter your maniaplanet server information.

	2.2	Open the file 'configs/database.xml'
			Enter your mysql server information.

	2.3	Open the file 'configs/authentication.xml'.
			Add the player logins of administrators.

3.	Run the tool via the shell script 'ManiaControl.sh' (UNIX) or the batch file 'ManiaControl.bat' (Windows)

4.	Enjoy!


INFORMATION:

- ManiaControl is only tested on UNIX machines
	- even though it might run properly on Windows we can't promise it will work all the time
	- furthermore we can't promise that there won't be a feature in the future that makes it impossible to run ManiaControl on Windows
	- in order to run ManiaControl on Windows you have to alter the file ManiaControl.bat and enter the path to your php.exe

- Tests were performed using PHP Version 5.4
- If you notice problems with other version please let me know

- Please report bugs by writing a mail to bugs@maniacontrol.com
