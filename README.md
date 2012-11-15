# WordPress-Functionality-Plugin-Skeleton

The skeleton for a WordPress functionality plugin. Contains a few common functions, and can be easily extended. 


## Features

* Redirects outbound e-mail from staging/development servers to the site administrator, so that users don't see tests or other unwanted messages.
* Blocks customized plugins from being accidentally upgraded (which would overwrite the customizations).
* Adds a flag in the footer output for an external monitoring service to check with a content sensor.


## Installation

* cd /var/www/vhosts/example.com/content/plugins
* git clone https://github.com/iandunn/WordPress-Functionality-Plugin-Skeleton.git plugin-slug
* cd plugin-slug
* git remote rm origin
* git rm README.md
* Update WordPress plugin headers
* Reset version number to 0.1
* Find/replace name
* git mv filenames to match new name
* Update the values of the $customizedPlugins array
* Update the value of the PRODUCTION_SERVER_NAME constant to match your production server name (e.g., "www.example.com")


## TODO

* Bring in best practices, coding standards, etc from plugin skeleton, but keep this lightweight


## License

This is free and unencumbered software released into the public domain.

Anyone is free to copy, modify, publish, use, compile, sell, or
distribute this software, either in source code form or as a compiled
binary, for any purpose, commercial or non-commercial, and by any
means.

In jurisdictions that recognize copyright laws, the author or authors
of this software dedicate any and all copyright interest in the
software to the public domain. We make this dedication for the benefit
of the public at large and to the detriment of our heirs and
successors. We intend this dedication to be an overt act of
relinquishment in perpetuity of all present and future rights to this
software under copyright law.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR
OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.

For more information, please refer to <http://unlicense.org/>