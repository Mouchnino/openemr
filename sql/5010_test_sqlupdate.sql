ALTER TABLE `x12_partners` ADD `x12_isa01` VARCHAR( 2 ) NOT NULL DEFAULT '00' AFTER `processing_format` ,
ADD `x12_isa02` VARCHAR( 10 ) NOT NULL DEFAULT '          ' AFTER `x12_isa01` ,
ADD `x12_isa03` VARCHAR( 2 ) NOT NULL DEFAULT '00' AFTER `x12_isa02` ,
ADD `x12_isa04` VARCHAR( 10 ) NOT NULL DEFAULT '          ' AFTER `x12_isa03` ;

