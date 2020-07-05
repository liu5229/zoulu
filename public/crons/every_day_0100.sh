#!/bin/sh

CRONDIR=/sites/walk/phpapi/public/crons/


php $CRONDIR/not_receive_gold.php
php $CRONDIR/remove_user_news_gold_after30.php
php $CRONDIR/report.php