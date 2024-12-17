use sys;

drop database if exists diary;
create database `diary`;

drop user if exists 'diary'@'%';
create user 'diary' identified by 'Y2UyOTRiNW';

GRANT CREATE, DROP, ALTER, SELECT, INSERT, UPDATE, DELETE ON `diary`.* TO 'diary'@'%';
