#My Encrypted Access

A small PHP, command line tool that allows storage, retrieval and searching encrypted files in Google Drive.
It's main focus is to allow people that have a "passwords" file stored in Google Drive or even on their desktop to safely store it and have easy access to it's content.
I wrote it to protect my crypto wallets passphrases and private keys as well as other sensitive data.

## Installation and Usage

* Fire up the project 

	`git clone https://github.com/the-codepunker/my-encrypted-access.git && composer install`

* Create a project with (Google Cloud)[https://console.cloud.google.com/apis] and download the client secret and client ID as json. Place it as secret.json in the root folder of the repo.

* Modify the `__FILE_NAME__` constant in `access.php` according to your needs. 

* Upload an empty txt file (named exactly the same) in any of your private Google Drive folders

* Place your "sensitive" file (named exactly the same) in the root of the project and run php access.php ... 

* Run `php access.php` and go through the "authorization process"... 
	1. A url will be generated in the command line - use that to grant access
	2. Google will generate a token which you can paste back into the command line

* Once authorized, you will be asked "What to do next ?". The first step is to encrypt the local file and push it to drive so type: `encrypt` and then confirm when asked if you want to push to gDrive.

* The app is built as a "step by step" wizard... so just follow the instructions...

**!!!Make sure to never push the secret.json and credentials.json files if you choose to fork this project!!!**

Donations are welcome: 

	ETH :: 0x9335fE2BCdca68407ed5Ae5FB196d2c69CAf96Da
	BTC :: 3MxwhHQPHNhdDTfL3XyYGy7hdxCdZ1wVmp
