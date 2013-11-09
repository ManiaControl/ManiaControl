**********************************************
*															*
*		mc ManiaPlanet Server Control		*
*				Written by steeffeen					*
*		Contact: mail@steeffeen.com				*
*															*
**********************************************

SETUP:

1.	Copy all files into your desired directory.

2.	Configure the needed settings:

	2.1	Open the file 'configs/server.mc.xml'.
			Enter your maniaplanet server information.

	2.2	Open the file 'configs/database.mc.xml'
			Enter your mysql server information or disable database usage if you don't have a mysql server available.

	2.3	Open the file 'configs/authentication.mc.xml'.
			Add the player logins who should have access to the commands of mc. 

3.	(Optional) Enable or disable the available plugins in the file 'configs/plugins.mc.xml'.

4.	(Optional) Edit the other config files in 'configs/' in order to customize your mc to fit your needs.

5.	Run the tool via the shell script 'mc.sh' (UNIX) or the batch file 'mc.bat' (Windows)

6.	Enjoy!


INFORMATION:

- mc is only tested on UNIX machines
	- even though it might run properly on Windows I can't promise it will work all the time
	- furthermore I can't promise that there won't be a feature in the future that makes it impossible to run mc under Windows
	- in order to run mc under Windows you have to alter the file mc.bat and enter the path to your php.exe

- Tests were performed using PHP Version 5.4
- If you notice problems with other version please let me know

- Please report bugs by writing a mail to mail@steeffeen.com
