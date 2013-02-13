Selenium2php
==========================

###Description
Converts HTML text of Selenium test case recorded from Selenium IDE into
PHP code for PHPUnit_Extensions_SeleniumTestCase as TestCase file.

###Usage
  selenium2php [switches] Test.html [Test.php]
  selenium2php [switches] <directory>

  --php-prefix=<string>          Add prefix to php filenames.
  --php-postfix=<string>         Add postfix to php filenames.
  --browser=<browsers string>    Set browser for tests.
  --browser-url=<url>            Set URL for tests.
  --remote-host=<host>           Set Selenium server address for tests.
  --remote-port=<port>           Set Selenium server port for tests.
  -r|--recursive                 Use subdirectories for converting.


###License
This is available under the Apache License, Version 2.0
 * Copyright 2013 Rnix Valentine
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
