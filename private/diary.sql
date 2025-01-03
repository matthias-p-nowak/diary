-- 2024-12-16 function that detects the presence of a column

drop function if exists ${prefix}ColumnCount;

create function `${prefix}ColumnCount` (tabName varchar(64), colName varchar(64)) returns int
READS SQL DATA
DETERMINISTIC
begin
    declare result int;
    set result = (select count(1) from information_schema.columns  
         where TABLE_SCHEMA = database() and
         TABLE_NAME = CONCAT('${prefix}',tabName) and COLUMN_NAME = colName 
        );
    return result; 
end;

-- 2024-12-16 function that detects the presence of an index

drop function if exists ${prefix}IndexCount;

create function `${prefix}IndexCount` (tabName varchar(64), idxName varchar(64)) returns int
READS SQL DATA
DETERMINISTIC
begin
    declare result int;
    set result = (select count(1) from information_schema.statistics  
         where TABLE_SCHEMA = database() and
         TABLE_NAME = tabName and INDEX_NAME = idxName 
        );
    return result; 
end;

-- 2024-12-17 Password

create table if not exists `${prefix}Password` (
    `Hash` varchar(64),
    `Created` timestamp not null default current_timestamp primary key,
    `Used` timestamp
);
-- 2024-12-21 Event

create table if not exists `${prefix}Event` (
    `Id` BIGINT NOT NULL AUTO_INCREMENT primary key, 
    `Activity` VARCHAR(255) NOT NULL , 
    `Details` VARCHAR(255),
    `Started` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , 
    `Ended` TIMESTAMP DEFAULT NULL,
    `IP` VARCHAR(255),
    `Latitude` REAL,
    `Longitude` REAL,
    UNIQUE `u_started` (`Started`)
);

-- 2024-12-27 default value for activity of table is empty

if not exists (select 1 from `${prefix}Event`) then
    insert into `${prefix}Event`(`Activity`) values('first');
end if;

-- 2024-12-28 adding activity

CREATE TABLE IF NOT EXISTS `${prefix}Activity`(
    `Activity` VARCHAR(255) NOT NULL PRIMARY KEY,
    `Results` BOOLEAN NOT NULL DEFAULT FALSE,
    `Parent` VARCHAR(255)
);

-- 2024-12-28 more things

create table if not exists `${prefix}Accounted` (
    `Activity` VARCHAR(255) NOT NULL,
    `Day` INT NOT NULL,
    `YearWeek` INT NOT NULL,
    `WeekDay` INT NOT NULL,
    `Sofar` float NOT NULL default 0,
    `Accounted` float NOT NULL default 0,
    `DayAccount` float NOT NULL default 0,
    primary key (`Activity`,`day`)
);

-- 2024-12-29 adding columns
if ${prefix}ColumnCount('Accounted','YearWeek') < 1 then
    alter table `${prefix}Accounted` add column `YearWeek` int default 0;
end if;

if ${prefix}ColumnCount('Accounted','WeekDay') < 1 then
    alter table `${prefix}Accounted` add column `WeekDay` int default 0;
end if;

if ${prefix}ColumnCount('Accounted','Accounted') < 1 then
    alter table `${prefix}Accounted` add column `Accounted` float not null default 0;
end if;


if ${prefix}ColumnCount('Accounted','DayAccount') < 1 then
    alter table `${prefix}Accounted` add column `DayAccount` float not null default 0;
end if;

-- 2024-12-30 getting on with configure

if ${prefix}ColumnCount('Activity','Results') < 1 then
    alter table `${prefix}Activity` add column `Results` boolean not null default false;
end if;
-- 2025-01-01 continue 

-- 2025-01-03 adding bigger IP
if ${prefix}ColumnCount('Event','IP') < 1 then
    alter table `${prefix}Event` add column `IP` VARCHAR(255);
end if;

-- 2025-01-03 and ...