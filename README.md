* Please note this project was deprecated years ago. You'd be insane to use it. *

GETTING THE DEMO WORKING

Virtual Host Configuration
- If you are working locally edit your hosts file to point a domain to 127.0.0.1
- Set the document root of your virtual host to the www folder
- Open up the browser and type in your domain

CREATING YOUR OWN APPLICATION

You can either modify the demo application in package/app or copy it to make your own site.

Setup
- Copy the files to another folder inside package
- Rename that folder to your application name
- Copy config/default.conf to config/[yourdomainname].conf

Config
- config/[yourdomainname].conf
- Set the APP_DIR to point to the folder with your application
- Fill in the database credentials if you need them

Virtual Host Configuration
- Create a subdomain (e.g. resource.yourdomain.com) and point the virtual host the resource folder ([APP_DIR]/resource)
- put your css/images and javascript inside app/resource
- Restart apache

ADDING ZEND CLASSES (OPTIONAL)

- Download the Zend framework
- Copy the classes you want into package/Zend
