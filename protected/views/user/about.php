<div id="content">

    <div class="you">
        <?php
        $this->renderPartial('/user/_sidebar', array(
            'user' => $user,
                )
        );
        ?>
        <div class="verticalRule">
            <img src="/webassets/images/you/profile.divider.png" />
        </div>
        <div class="youContent">
            <h1>ABOUT</h1>
            <div class="aboutTextBox" style="font-family: arial; font-size: 13px; text-align: justify;padding:20px;">
                INITECH 5 is a leading source of TV news and entertainment in Los Angeles.  INITECH 5 produces more hours of local news in L.A. than any other TV station in the city.  Channel 5 is also home of the award-winning INITECH 5 Morning News, the #1 local morning news show in L.A. INITECH 5 is also home to many special events like the L.A. Marathon, Red Carpet coverage of the Emmys and Academy Awards and its long-running award-winning annual coverage of the Tournament of Roses Parade.
            </div>
        </div>
    </div>
</div>