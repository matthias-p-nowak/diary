-- 2024-12-16 function that detects the presence of a column

drop function if exists ${prefix}ColumnCount;

create function `${prefix}ColumnCount` (tabName varchar(64), colName varchar(64)) returns int
READS SQL DATA
DETERMINISTIC
begin
    declare result int;
    set result = (select count(1) from information_schema.columns  
         where TABLE_SCHEMA = database() and
         TABLE_NAME = tabName and COLUMN_NAME = colName 
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
    `IP` VARCHAR(16),
    `Latitude` REAL,
    `Longitude` REAL,
    UNIQUE `u_started` (`Started`)
);

