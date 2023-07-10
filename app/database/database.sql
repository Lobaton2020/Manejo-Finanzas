drop database if exists finances;
create database finances;
use finances;

create table documenttypes(
    id_document_type  int not null auto_increment,
    abrev varchar(10),
    name varchar(100),
    primary key(id_document_type)
);

create table rols(
    id_rol int not null auto_increment,
    name varchar(100),
    primary key(id_rol)
);

create table users(
    id_user int not null auto_increment,
    id_rol  int not null,
    id_document_type  int not null,
    number_document varchar(100) not null,
    complete_name varchar(100) not null,
    email varchar(200) not null,
    password varchar(1000) not null,
    image varchar(500) null,
    email_verify_date datetime null,
    recovery_pass_token varchar(200) null,
    remember_token varchar(200) null,
    born_date date null,
    status  boolean not null,
    update_at datetime not null,
    create_at datetime not null,
    primary key (id_user),
    foreign key(id_document_type) references documenttypes (id_document_type),
    foreign key(id_rol) references rols (id_rol)
);

create table tokenregisters(
    id_token_register int not null auto_increment,
    id_rol int not null,
    id_user int not null,
    description varchar(200) null,
    token varchar(200) not null,
    status  boolean not null,
    create_at datetime not null,
    primary key(id_token_register),
    foreign key(id_user) references users (id_user)
);

create table moneyloans(
    id_money_loan int not null auto_increment,
    id_user int not null,
    description varchar(200) null,
    total float not null,
    set_date date not null, -- fecha de devolucion
    status  boolean null,
    create_at datetime not null,
    primary key(id_money_loan),
    foreign key(id_user) references users (id_user)
);

create table notes(
    id_note int not null auto_increment,
    id_user int not null,
    description text null,
    total float not null,
    status  boolean null,
    create_at datetime not null,
    primary key(id_note),
    foreign key(id_user) references users (id_user)
);

create table countvisits(
    id_count_visit int not null auto_increment,
    id_user int not null,
    count bigint not null,
    update_at datetime not null,
    create_at datetime not null,
    primary key(id_count_visit)
);

create table loggins(
    id_login int not null auto_increment,
    id_user int not null,
    browser varchar(300) not null,
    server varchar(100)not null,
    create_at datetime not null,
    primary key(id_login),
    foreign key(id_user) references users (id_user)
);

create table notificationtypes(
key_notification_type varchar(100) not null,
name varchar(200) not null,
primary key(key_notification_type)
);

create table notifications(
    id_notification int not null auto_increment,
    id_user int not null,
    key_notification_type varchar(100) not null,
    readed boolean not null,
    create_at datetime not null,
    primary key(id_notification),
    foreign key(id_user) references users (id_user),
    foreign key(key_notification_type) references notificationtypes (key_notification_type)
);

create table porcents(
    id_porcent int not null auto_increment,
    id_user int null ,
    name varchar(100) not null,
    status  boolean not null,
    create_at datetime not null,
    primary key (id_porcent)
);

create table inflowtypes(
    id_inflow_type int not null auto_increment,
    id_user int null ,
    name varchar(200) not null ,
    status  boolean not null,
    create_at datetime not null,
    primary key (id_inflow_type)

);
create table outflowtypes(
    id_outflow_type int not null auto_increment,
    id_user int null ,
    name varchar(200) not null ,
    status  boolean not null,
    create_at datetime not null,
    primary key (id_outflow_type)

);

create table inflows (
    id_inflow int not null auto_increment,
    id_user int not null ,
    id_inflow_type int not null,
    total float not null,
    description mediumtext null,
    set_date  date not null ,
    status  boolean not null,
    update_at datetime not null,
    create_at datetime not null,
    primary key (id_inflow),
    foreign key(id_user) references users (id_user),
    foreign key(id_inflow_type) references inflowtypes (id_inflow_type)
);

create table categories(
    id_category int not null auto_increment,
    id_outflow_type  int not null ,
    id_user int null ,
    name varchar(200) not null ,
    status  boolean not null,
    create_at datetime not null,
    primary key (id_category)
);

create table outflows (
    id_outflow int not null auto_increment,
    id_outflow_type int not null,
    id_user int not null,
    id_category int  null,
    id_porcent int not null,
    amount  float not null,
    description mediumtext null,
    set_date  date not null ,
    status  boolean not null,
    update_at datetime not null,
    create_at datetime not null,
    primary key (id_outflow),
    foreign key(id_category) references categories (id_category),
    foreign key(id_user) references users (id_user),
    foreign key(id_outflow_type) references outflowtypes (id_outflow_type)
);

create table inflow_porcent (
    id_inflow_porcent int not null auto_increment,
    id_inflow int not null,
    id_porcent int not null,
    porcent int  null,
    status  boolean not null,
    create_at datetime not null,
    primary key (id_inflow_porcent),
    foreign key(id_inflow) references inflows (id_inflow),
    foreign key(id_porcent) references porcents (id_porcent)
);

create table queries(
    id_query int not null auto_increment,
    id_user int not null,
    description text,
    query text,
    create_at datetime not null,
    primary key (id_query),
    foreign key(id_user) references users (id_user)
);
-- This table represents the budgets in the main option to do monitoring
create table budget(
    id_budget int not null auto_increment,
    id_user int not null,
    total float not null,
    description text,
    created_at datetime not null default now(),
    primary key (id_budget),
    foreign key(id_user) references users (id_user)
);

ALTER TABLE outflows
ADD is_in_budget boolean not null default false;

create or replace view view_budget as
    SELECT id_budget, id_user, budget, total_a as total, IFNULL(budget - total_a, 0) as remain, IFNULL( floor(100 * total_a / budget),0) as percent, date
    FROM (
        SELECT budget.id_budget,
            budget.id_user,
            budget.total as budget,
            convert(CONCAT(year(budget.created_at),'-', month(budget.created_at),'-','01'),DATE) as date,
            (SELECT IFNULL(SUM(outflows.amount),0) FROM outflows
                WHERE id_user = budget.id_user
                    AND EXTRACT(YEAR FROM budget.created_at) = EXTRACT(YEAR FROM outflows.set_date)
                    AND EXTRACT(MONTH FROM budget.created_at) = EXTRACT(MONTH FROM outflows.set_date)
                    AND outflows.is_in_budget = true) as total_a
        FROM budget
        INNER JOIN users ON budget.id_user = users.id_user
        ORDER BY created_at DESC
    ) AS t

-- This table representa the budgets in the module in the UI
create table temporal_budgets(
    id_temporal_budget int not null auto_increment,
    id_user int not null,
    name varchar(255),
    description text null,
    created_at datetime not null default now(),
    primary key (id_temporal_budget),
    foreign key(id_user) references users (id_user)
);

create table temporal_budgets_outflow (
    id_temporal_budget_outflow int not null auto_increment,
    id_temporal_budget int not null,
    id_outflow_type int not null,
    id_user int not null,
    id_category int null,
    id_porcent int not null,
    amount float not null,
    description mediumtext null,
    status boolean not null,
    is_in_budget boolean not null default false,
    update_at datetime not null,
    create_at datetime not null,
    primary key (id_temporal_budget_outflow),
    foreign key(id_category) references categories (id_category),
    foreign key(id_user) references users (id_user),
    foreign key(id_outflow_type) references outflowtypes (id_outflow_type),
    foreign key(id_temporal_budget) references temporal_budgets (id_temporal_budget)
);

CREATE
OR REPLACE view view_temporal_budgets AS
SELECT
    tb.*,
    COALESCE(SUM(otb.amount), 0) AS total_amount
FROM
    temporal_budgets tb
    LEFT JOIN temporal_budgets_outflow otb ON tb.id_temporal_budget = otb.id_temporal_budget
    and otb.status = 1
GROUP BY
    tb.id_temporal_budget;

create table investments(
    id_investment int not null auto_increment,
    id_outflow int not null,
    percent_annual_effective float not null default 0,
    state varchar(255) not null,
    init_date date not null default (CURRENT_DATE + INTERVAL 0 MONTH),
    end_date date not null default (CURRENT_DATE + INTERVAL 1 MONTH),
    real_retribution float not null default 0,
    risk_level varchar(255) null,
    updated_at datetime not null default CURRENT_TIMESTAMP,
    created_at datetime not null default CURRENT_TIMESTAMP,
    primary key (id_investment),
    foreign key(id_outflow) references outflows (id_outflow)
);

create or replace view investments_view as
SELECT i.*, o.id_user, o.amount, o.description, c.name,
       (o.amount * ((i.percent_annual_effective / 100) / 12) *
       (PERIOD_DIFF(EXTRACT(YEAR_MONTH FROM i.end_date), EXTRACT(YEAR_MONTH FROM i.init_date)))) as earn_amount
FROM investments i
INNER JOIN outflows o ON o.id_outflow = i.id_outflow
INNER JOIN users u ON u.id_user = o.id_user
INNER JOIN categories c ON o.id_category = c.id_category;

insert into rols values (1,"Administrador"),
                        (2,"Usuario");
 insert into notificationtypes values ("register"," se ha registrado por medio de un token"),
                                      ("recoverypassword"," esta recuperando la contraseña"),
                                      ("ingres"," ha hecho un ingreso"),
                                      ("egress"," ha hecho un egreso"),
                                      ("deposit"," ha creado un deposito"),
                                      ("category"," ha creado una categoria de egreso"),
("token", " ha creado un token de registro"),
(
    "budget-item",
    " ha creado un elemeento de notificacion"
),
("budget", " ha creado un presupuesto");


insert into documenttypes values(1,"T.I","Tarjeta de identídad"),
                                (2,"C.C","Cédula de ciudadanía");

insert into outflowtypes values (1,1,"Inversion",1,now()),
                                (2,1,"Gasto",1,now());

insert into inflowtypes values  (1,1,"Salario",1,now()),
                                (2,1,"Jovenes en accion",1,now());

insert into categories values (1,1,1,"Finca raiz",1,now()),
                              (2,1,1,"Ganado",1,now()),
                              (3,1,1,"Casa",1,now()),
                              (4,2,1,"Arriendo",1,now()),
                              (5,2,1,"Servicios",1,now()),
                              (6,2,1,"Mercado",1,now()),
                              (7,2,1,"Deudas",1,now());

insert into porcents values (1,1,"Para mi",1,now()),
                            (2,1,"Ahorro",1,now()),
                            (3,1,"Ayudar en casa",1,now()),
                            (4,1,"Gastos en general",1,now());

select p.*,sum(i.total * (ip.porcent / 100)) as total  from porcents as p
                                                    left join inflow_porcent as ip on ip.id_porcent = p.id_porcent
                                                    left join inflows as i on ip.id_inflow = i.id_inflow  where p.id_user = 1
                                                    group by p.id_porcent ORDER BY ip.id_porcent ASC;
                            -- password:12345
insert into users values(1,1,1,"2001202","Andres Lobaton","andrespipe021028@gmail.com","$2y$10$ElyCcmqbM0N79j5qBzkJkeHe42q4/6grB3LV8YAWZzPA/ErGa/XDy",null,null,null,null,null,1,"2020-09-17 02:37:12","2020-09-17 02:37:12");

INSERT INTO queries VALUES
(1,1, 'Consulta de total de salidas de dinero por categorias', 'select cat.name,sum(amount) as total from outflows as outf inner join categories as cat on cat.id_category = outf.id_category where outf.id_user = 1 GROUP by outf.id_category', '2021-01-16 18:56:36'),
(2,1, 'Consultar el total de ingresos por tipo de ingresos', 'select it.name,sum(total) as total from inflows as inf inner join inflowtypes as it on inf.id_inflow_type = it.id_inflow_type where inf.id_user = 1 GROUP by inf.id_inflow_type', '2021-01-16 19:03:54'),
(3,1, 'Consultar el total de ingresos por tipo de ingresos, y por fecha delimitada (inicio-fin)', 'select it.name,sum(total) as total from inflows as inf \r\ninner join inflowtypes as it on inf.id_inflow_type = it.id_inflow_type \r\nwhere inf.id_user = 1 and (inf.create_at BETWEEN \"2020-10-01\" AND now())\r\nGROUP by inf.id_inflow_type', '2021-01-16 19:10:05');
