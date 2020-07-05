# CranberryMail: A web based email client with modern UI and UX

Software required for Building CranberryMail
-------------------------------------------
1. nvm 
2. node.js LTS version 10
3. yarn package manager
4. LAMP or MAMP server with PHP 7.3, MySql and Apache 2

Apache modules required
-----------------------
1. mod_rewrite

PHP extensions required
-----------------------
1. BCMath
2. Ctype
3. Fileinfo
4. JSON
5. Mbstring
6. OpenSSL
7. PDO
8. Tokenizer
9. XML (SimpleXML)
10. MySQLi

Steps for building production ready CranberryMail from git repository
------------------------------------------------------------------------
1. Clone the repository to your pc or laptop
2. cd `<repository>`
3. Run `nvm use` to use node.js 10 LTS edition
4. Run `yarn install` to install all the dependencies 
5. Run `yarn clean` to start with a fresh build
6. Run `yarn build` to get a production ready build of CranberryMail as a zip file in `releases` folder

Installation and Setup the CranberryMail software
------------------------------------------------------------------------
1. Install the above listed softwares before installing CranberryMail on your server or local pc/laptop.
2. Make `AllowOverride All` in your apache.conf for your server root folder to enable overrides by .htaccess
3. Copy the setupv3.php file to your root folder
4. In the browser type your application path like: `http://localhost/setupv3.php`
5. Follow the instructions on the screen and by the end of this installation you will have a working copy of CranberryMail on your server or local pc/laptop
6. Share your CranberryMail url with your team and ask them to login with their username and password to access their emails

Upgrading existing CranberryMail installation
---------------------------------------------
1. Clone the repository to a new directory or do a `git pull` inside the existing cloned directory.
2. Inside the cloned or existing directory run the command `nvm use`
3. Run `yarn install`;
4. Next run `yarn clean` and;
5. Finally run `yarn build`
6. Go to the releases folder in the root level of the current directory and copy the generated zip file.
7. Use the setup file and in the installation method choose upload zip file
8. Upload the zip file obtained in step 5
9. Finally follow the on-screen instructions to complete the upgrade process


.env file variables
-------------------
1. Type of environment: APP_ENV (local or production)
2. Allow debugging in app: APP_DEBUG (true or false)
3. Logging level: APP_LOG_LEVEL (debug or info or notice or warning or error or critical or alert or emergency) 

The above value for log levels are mentioned from least severe to most severe. Once this option has been configured, Laravel will log all levels greater than or equal to the specified severity.


Troubleshooting issues during setup process
-------------------------------------------
1. File Upload

   Update your php.ini to the following values
   
    file_uploads = On
    post_max_size = 100M
    upload_max_filesize = 100M

   Restart your apache server after modifying php.ini and you can resume the setup process.

2. Continuous page redirection

   If you are facing this issue then go into the settings of your browser and clear the browser cache. 

   You can also try private browsing mode or incognito mode in your browser to get rid off this error.


Contribute
----------
Want to contribute to CranberryMail. Email us at contribute+cranberrymail@oss.nettantra.com


Support / Contact
-----------------
Email us at support@oss.nettantra.com


