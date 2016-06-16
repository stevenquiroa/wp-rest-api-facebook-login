WP REST API - Facebook Login
==============================

Pre-Pequisites

* Wordpress 4.5.2

Preference

* Oauth1a Server Plugin Installed

Note: If you don't have Oauth1a Server Plugin installed, then modify function get_basic_auth and get_user_and_app_auth into WP_REST_API_FB_Login_Controller to return true when current_user_can 

Installation Instructions
--------------------------
Modify includes/facebook_config.php.example to facebook_config.php and set your own facebook credentials

Tester Insturctions
--------------------------
Pre-Pequisites:

* node v6.2.1

npm install on folder tester.
Set tester/env.json and tester/facebook.json fields.

If you want to use mini-server to get facebook user access token, set into code of tester/server/index.html app_id