<?php

if ($_SERVER['REQUEST_URI'] == '/.akka/healthz') {
    echo time();
    exit();
}
