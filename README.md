# cheese-check-in
A horrible hack to check in to cheese.

Full blog - https://shkspr.mobi/blog/2017/04/creating-a-generic-open-source-check-in-app/

## Requirements

* PHP 7+
* [HybridAuth](https://github.com/hybridauth/hybridauth/)
* [Twitter API keys](http://apps.twitter.com/)
* mySQL

You'll need a database with three tables:

```
CREATE DATABASE CHEESE;

USE CHEESE;

CREATE USER 'cheeser'@'localhost' IDENTIFIED BY 'password';
GRANT INSERT ON CHEESE . * TO 'cheeser'@'localhost';
GRANT SELECT ON CHEESE . * TO 'cheeser'@'localhost';

CREATE TABLE users(
   user_id VARCHAR(36) NOT NULL,
   twitter_username VARCHAR(128) NOT NULL,
   twitter_id VARCHAR(128) NOT NULL,
   PRIMARY KEY ( user_id )
);

CREATE TABLE cheeses(
   cheese_id VARCHAR(36) NOT NULL,
   cheese_name VARCHAR(128) NOT NULL,
   cheese_url VARCHAR(128) NOT NULL,
   PRIMARY KEY ( cheese_id )
);

CREATE TABLE checkins(
   checkin_id VARCHAR(36) NOT NULL,
   checkin_time DATETIME NOT NULL,
   user_id VARCHAR(36) NOT NULL,
   cheese_id VARCHAR(36) NOT NULL,
   comment TINYTEXT,
   PRIMARY KEY( checkin_id ),
   FOREIGN KEY( user_id ) references users( user_id ),
   FOREIGN KEY( cheese_id ) references cheeses( cheese_id )
);
```
