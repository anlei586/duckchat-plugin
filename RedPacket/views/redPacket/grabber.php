<html>

<head>
    <title>红包</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="stylesheet" href="../../public/css/grabber.css?v=1"/>
</head>
<style>
</style>

<body>
<div class="wrapper">
    <div><img class="user-avatar" src="<?php echo $sendUserAvatar; ?>"></div>
    <div class="red-center">
        <div class="send-user"><?php echo $sendUserNickname; ?></div>
    </div>
    <div class="red-center">
        <div class="red-desc"><?php echo $redPacketDesc; ?></div>
    </div>
    <div class="red-center">
        <div class="red-amount"><?php echo $redPacketAmount; ?></div>
    </div>

    <div class="layout-all-row">

        <div class="list-item-center">
            <div class="item-title">
                <div class="item-title-content"><?php echo $redPacketTip; ?></div>
            </div>
            <div class="division-line"></div>


            <?php foreach ($redPacketGrabbers as $grabber) { ?>
                <div class="item-row">
                    <div class="item-header">
                        <img class="grabber-avatar" src="<?php echo $grabber['avatar']; ?>"/>
                    </div>
                    <div class="item-body">
                        <div class="item-body-display">
                            <div class="item-body-desc">
                                <div class="grab-user"><?php echo $grabber['nickname']; ?></div>
                                <div class="grab-time"><?php echo date("H:i", $grabber['grabTime'] / 1000); ?></div>
                            </div>
                            <div class="item-body-tail">
                                <div class="grab-amount"><?php echo $grabber['amount']; ?> 元</div>

                                <?php if (isset($grabber["luckDucker"])) { ?>
                                    <div class="grab-top">
                                        <div><img class="luck-star" src="./public/img/luck-star.png"></div>
                                        <div>手气最佳</div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="division-line"></div>

            <?php } ?>

        </div>

    </div>
</div>

</body>

</html>