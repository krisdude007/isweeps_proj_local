            
            <table cellspacing="0" cellpadding="0" class="fab-a-blue  ">
                <tbody>
                    <?php
                    foreach($rows as $key => $value){
                        $parameters = array('label' => $key, 'value' => $value);
                        $this->renderPartial('_reportRowToggle', Array('parameters' => $parameters, 'id' => $id));
                        break;// Only do the 1st one which is the header toggler. The rest are built down there \/\/\/\/
                    }
                    ?>
                </tbody>
            </table>
            
            <table <?php echo isset($id) ? 'id="'.$id.'"' : ""; ?>  cellspacing="0" cellpadding="0" style="display:none;" class="fab-a-blue ">
                <tbody>
                    <?php
                    $i = 0;
                    foreach($rows as $key => $value){
                        if($i != 0){ //skip the 1st one which is the header toggler up there /\/\/\/\
                            $parameters = array('label' => $key, 'value' => $value);
                            $this->renderPartial('_reportRow', Array('parameters' => $parameters));
                        }
                        $i++;
                    }
                    ?>
                </tbody>
            </table>