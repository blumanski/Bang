# bang
Simple PHP Framework - Core Files

**Including**

**PDO-Wrapper**
* Added slow query logger to identify bottlenecks and slow queries.
* Added error logger
* Added query result cache which is using Redis DB
 
**Router**
Very simple at the moment.

**Module Loader**
Load dynamically modules

**Redis Session Handler**

**Views**
Easy to overwrite view with two examples, one responder view (ajax/API) and one web view

**Language**
This is a multi-language system, it is using ini files which are getting combined into an array.
Modules come with their own language files.

**Modules**
The framework uses a modular structure, each module is independent in it's own directory, including all files, including language, sass, js and images.
Each module is supposed to be a GitHub repository to be able to use composer to install it.

**Tools**
I added to mini wrappers for AWS S3 and Pusher.

Well, this is my fun project which I use to relax in my free time.

Don't use this as it is in heavy development and just started.
