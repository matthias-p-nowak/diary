use sys;

drop database if exists diary;
create database `diary`;

drop user if exists 'diary'@'%';
create user 'diary' identified by 'Y2UyOTRiNW';

GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, CREATE TEMPORARY TABLES, EXECUTE, 
    CREATE VIEW, SHOW VIEW, CREATE ROUTINE, ALTER ROUTINE, EVENT, TRIGGER ON `diary`.* TO `diary`@`%`;
