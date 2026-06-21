<?php
require 'db.php';
try {
    getConexao();
    echo "connect-ok";
} catch (Exception $e) {
    echo 'error: ' . $e->getMessage();
}
