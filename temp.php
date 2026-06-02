<?php require 'api.pos/models/connection.php';  = Connection::connect();  = ->query('DESCRIBE cashs'); print_r(->fetchAll(PDO::FETCH_ASSOC)); 
