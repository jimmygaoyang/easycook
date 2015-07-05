SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE=TRADITIONAL;

DROP SCHEMA IF EXISTS CookMaster ;
CREATE SCHEMA IF NOT EXISTS CookMaster DEFAULT CHARACTER SET utf8 ;
USE CookMaster;

drop table if exists Box;

drop table if exists Brand;

drop table if exists Material;

drop table if exists Material_Kind;

drop table if exists Me_Ma_Association;

drop table if exists Menu;

drop table if exists Role;

drop table if exists Step;

drop table if exists User;

DROP TABLE IF EXISTS Device_Specification;

/*==============================================================*/
/* Table: Brand                                                 */
/*==============================================================*/
create table Brand
(
   Brand_Id             INT NOT NULL AUTO_INCREMENT,
   Name                 varchar(50) NOT NULL,
   CorporName           varchar(100),
   TelNum               varchar(20),
   Address              varchar(200),
   URL                  varchar(50),
   Ref                  INT,
   primary key (Brand_Id),
   UNIQUE INDEX Brand_Name_UNIQUE (Name ASC)
)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


/*==============================================================*/
/* Table: Material                                              */
/*==============================================================*/
create table Material
(
   Material_Id          int NOT NULL AUTO_INCREMENT,
   Material_Kind_Id     int,
   Name                 varchar(200),
   Brand_Id             int,
   Ref                  int,
   primary key (Material_Id),
   CONSTRAINT Material_Brand_Id
    FOREIGN KEY (Brand_Id)
    REFERENCES Brand (Brand_Id )
    ON DELETE SET NULL
    ON UPDATE CASCADE,
   CONSTRAINT Material_Material_Kind_Id
    FOREIGN KEY (Material_Kind_Id)
    REFERENCES Material_Kind (Material_Kind_Id)
    ON DELETE SET NULL
    ON UPDATE CASCADE
)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


/*==============================================================*/
/* Table: Material_Kind                                         */
/*==============================================================*/
create table Material_Kind
(
   Material_Kind_Id     int NOT NULL AUTO_INCREMENT,
   Name                 varchar(100) not null,
   Description          varchar(400),
   primary key (Material_Kind_Id),
   UNIQUE INDEX Material_Name_UNIQUE (Name ASC)
)ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

/*==============================================================*/
/* Table: Me_Ma_Associationl                                    */
/*==============================================================*/
create table Me_Ma_Association
(
   Id                   int NOT NULL AUTO_INCREMENT,
   Menu_Id              int,
   Material_Id          int,
   Amount               int,
   primary key (Id),
CONSTRAINT Me_Ma_Associationl_Menu_Id
    FOREIGN KEY (Menu_Id)
    REFERENCES Menu (Menu_Id )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
CONSTRAINT Me_Ma_Associationl_Material_Id
    FOREIGN KEY (Material_Id)
    REFERENCES Material (Material_Id )
    ON DELETE NO ACTION
    ON UPDATE CASCADE
)ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


/*==============================================================*/
/* Table: Menu                                                  */
/*==============================================================*/
create table Menu
(
   Menu_Id              int NOT NULL AUTO_INCREMENT,
   Name                 varchar(200) not null,
   User_Id              int not null,
   CreateTime           datetime not null,
   UpdateTime           datetime not null,
   DownLoad             int,
   primary key (Menu_Id),
 CONSTRAINT Menu_User_Id
    FOREIGN KEY (User_Id)
    REFERENCES User (User_Id)
    ON DELETE NO ACTION
    ON UPDATE CASCADE  
)ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;



/*==============================================================*/
/* Table: Role                                                  */
/*==============================================================*/
create table Role
(
   Role_Id              int NOT NULL AUTO_INCREMENT,
   Name                 varchar(20) NOT NULL,
   Status               enum('inactive','active') not null,
   primary key (Role_Id)
)ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

/*==============================================================*/
/* Table: Step                                                  */
/*==============================================================*/
create table Step
(
   Step_Id              int NOT NULL AUTO_INCREMENT,
   Menu_Id              int not null,
   Content              text not null,
   FireLevel            int,
   Time                 int not null,
   Picture              mediumblob not null,
   primary key (Step_Id),
 CONSTRAINT Step_Menu_Id
    FOREIGN KEY (Menu_Id)
    REFERENCES Menu (Menu_Id )
    ON DELETE CASCADE
    ON UPDATE CASCADE  
)ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


/*==============================================================*/
/* Table: user                                                  */
/*==============================================================*/
create table User
(
   User_Id              int NOT NULL AUTO_INCREMENT,
   Role_Id              int,
   Password             varchar(200),
   Name                 varchar(20),
   Phone                varchar(20),
   UpLoad               int,
   DownLoad             int,
   Status               int,
   primary key (User_Id),
  CONSTRAINT User_Role_Id
    FOREIGN KEY (Role_Id)
    REFERENCES Role (Role_Id )
    ON DELETE NO ACTION
    ON UPDATE CASCADE   
)ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

/*==============================================================*/
/* Table: box                                                 */
/*==============================================================*/
create table Box
(
   Box_Id              int NOT NULL AUTO_INCREMENT,
   Box_Mac             VARCHAR(20) not null,
   User_Id             int,
   Material_Kind_Id    int,
   Material_Id         int,
   SW_Ver              VARCHAR(15),
   primary key (Box_Id),
  CONSTRAINT Box_User_Id
    FOREIGN KEY (User_Id)
    REFERENCES User (User_Id )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT Box_Material_Kind_Id
    FOREIGN KEY (Material_Kind_Id)
    REFERENCES Material_Kind (Material_Kind_Id )
    ON DELETE CASCADE
    ON UPDATE CASCADE,   
  CONSTRAINT Box_Material_Id
    FOREIGN KEY (Material_Id)
    REFERENCES Material (Material_Id )
    ON DELETE CASCADE
    ON UPDATE CASCADE   
)ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

-- -----------------------------------------------------
-- Table `Device_Specification`
-- -----------------------------------------------------


CREATE  TABLE IF NOT EXISTS Device_Specification(
  Device_Id INT(11) NOT NULL AUTO_INCREMENT ,
  Device_IP VARCHAR(15) NOT NULL ,
  Device_CUID VARCHAR(32) NOT NULL ,
  Device_Name VARCHAR(45) NOT NULL ,
  Device_Serial_No VARCHAR(32) NOT NULL ,
  User_Id INT NOT NULL ,
  SMS_No VARCHAR(32) NOT NULL ,
  Device_Status ENUM('active','inactive','sleep','dormant') NOT NULL DEFAULT 'inactive' ,
  Wakeup_Attempts INT(2) NOT NULL DEFAULT 0 ,
  Registration_Count INT(7) NULL ,
  Sleep_Time DATETIME NULL ,
  Last_Contact_Time DATETIME NULL ,
  Heartbeat_interval INT NOT NULL DEFAULT 0 ,
  Keep_Alive_Period INT(8) NULL ,
  Alarm ENUM('0','1') NULL ,
  Last_Modified DATETIME NOT NULL ,
  Description VARCHAR(32) NULL ,
  PRIMARY KEY (Device_Id) ,
  UNIQUE INDEX Hub_MAC_UNIQUE (Device_CUID ASC) ,
  UNIQUE INDEX Hub_Id_UNIQUE (Device_Id ASC) ,

  CONSTRAINT Device_Specification_User_Id
    FOREIGN KEY (User_Id)
    REFERENCES User (User_Id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
    )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;



insert into Role values(1,'admin','active');
insert into Role values(2,'user','active');
