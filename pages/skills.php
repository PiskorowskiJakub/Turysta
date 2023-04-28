
<div class="content">
    <div class="headerTitle">
        <h2>Ćwiczone umiejętności</h2>
    </div>
    <div class="mainBoxContent">
        <?php echo "Brak ćwiczonych umiejętności"; ?>
    </div>
</div>

<div class="content">
    <div class="headerTitle">
        <h2>Umiejętności podstawowe</h2>
    </div>
    <div class="skills">
        <div class="skill">
            <div class="mainBoxContent">
                <div class="circle-container"></div>
                <div class="title">Języki obce</div>
                <div class="imageWork">
                    <img src="img/skills/sword/sword.png"/>
                    <div class="text-container">
                        <p>Umiejętność ta umożliwia turystom nawiązanie kontaktu z miejscowymi mieszkańcami, zrozumienie ich kultury i zwyczajów oraz poruszanie się bez większych trudności w nieznanej okolicy.</p>
                    </div>
                </div>  
                <div class="timeLevelEarning">
                    <hr>
                    Czas: <b> <?php echo "00:20"; ?>  </b>
                    <hr>
                    Poziom: <b id="earning"> 0 / &#8734;</b>
                    <hr>
                </div>
                <form method="POST">
                    <input type="submit" id="startWorkMarket" name="startWorkMarket" value="<?php echo "12"." monet"; ?>" class="mainButton" onclick="startProgress()"/>
                </form>
            </div>
            <div class="mainBoxContent">
                <div class="badges">
                    <div class="imageWork"><img src="img/skills/sword/sword-bronze.png"/></div>
                    <div class="imageWork"><img src="img/skills/sword/sword-silver.png"/></div>
                    <div class="imageWork"><img src="img/skills/sword/sword-gold.png"/></div>
                    <div class="imageWork"><img src="img/skills/sword/sword-diamond.png"/></div>
                </div>
            </div>
        </div>

        <div class="skill">
            <div class="mainBoxContent">
                <div class="circle-container"></div>
                <div class="title">Nawigacja</div>
                <div class="imageWork">
                    <img src="img/skills/shield/shield.png"/>
                    <div class="text-container">
                        <p>Umiejętność ta pozwala turystom uniknąć zagubienia i pozwala na swobodne przemieszczanie się między atrakcjami turystycznymi i innymi interesującymi miejscami.</p>
                    </div>
                </div> 
                <div class="timeLevelEarning">
                    <hr>
                    Czas: <b> <?php echo "00:20"; ?>  </b>
                    <hr>
                    Poziom: <b id="earning"> 0 / &#8734;</b>
                    <hr>
                </div>
                <form method="POST">
                    <input type="submit" id="startWorkMarket" name="startWorkMarket" value="<?php echo "12"." monet"; ?>" class="mainButton" onclick="startProgress()"/>
                </form>
            </div>
            <div class="mainBoxContent">
            <div class="badges">
                    <div class="imageWork"><img src="img/skills/shield/shield-bronze.png"/></div>
                    <div class="imageWork"><img src="img/skills/shield/shield-silver.png"/></div>
                    <div class="imageWork"><img src="img/skills/shield/shield-gold.png"/></div>
                    <div class="imageWork"><img src="img/skills/shield/shield-diamond.png"/></div>
                </div>
            </div>
        </div>

        <div class="skill">
            <div class="mainBoxContent">
                <div class="circle-container"></div>
                <div class="title">Kultura</div>
                <div class="imageWork">
                    <img src="img/skills/scales/scales.png"/>
                    <div class="text-container">
                        <p>Umiejętność ta pozwala turystom lepiej zrozumieć kulturę odwiedzanego kraju, co może pomóc w uniknięciu nietaktownych zachowań i zwiększeniu szans na pozytywny odbiór ze strony miejscowych.</p>
                    </div>
                </div> 
                <div class="timeLevelEarning">
                    <hr>
                    Czas: <b> <?php echo "00:40"; ?>  </b>
                    <hr>
                    Poziom: <b id="earning"> 0 / &#8734;</b>
                    <hr>
                </div>
                <form method="POST">
                    <input type="submit" id="startWorkMarket" name="startWorkMarket" value="<?php echo "20"." monet"; ?>" class="mainButton" onclick="startProgress()"/>
                </form>
            </div>
            <div class="mainBoxContent">
            <div class="badges">
                    <div class="imageWork"><img src="img/skills/scales/scales-bronze.png"/></div>
                    <div class="imageWork"><img src="img/skills/scales/scales-silver.png"/></div>
                    <div class="imageWork"><img src="img/skills/scales/scales-gold.png"/></div>
                    <div class="imageWork"><img src="img/skills/scales/scales-diamond.png"/></div>
                </div>
            </div>
        </div>



    </div>
</div>