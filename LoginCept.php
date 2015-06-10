<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('perform actions and see result');
$I ->click('course-2');
$I ->click('module-8');	
$I ->click('Re-attempt quiz');
$I ->click('q5:1_-submit'); //boton check
$I ->click('yui_3_17_2_2_1433977749801_354'); //boton next 
$I ->click('single_button5578c291a454f3'); //enviar 


	