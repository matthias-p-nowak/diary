-- 2024-12-17 new start

create table if not exists `${prefix}Password` (
    `Hash` varchar(64),
    `Created` timestamp not null default current_timestamp,
    `Used` timestamp
);