<script type="text/javascript" src="http://maps.google.com/maps/api/js?key=<?= Yii::$app->params['google_api_key'] ?>"></script>
<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use backend\models\Customer;
use backend\models\Unit;
use dosamigos\datepicker\DatePicker;
use dosamigos\datetimepicker\DateTimePicker;
use kartik\select2\Select2;

$this->registerJs(
    "$('#order-order_hours').on('change', function() {
      if($(this).val() > 0){
        $.getJSON( \"".urldecode(Yii::$app->urlManager->createUrl('order/prices/'))."?id=\"+$('#order-p_master_unit_id').val()+\"&hours=\"+$(this).val(), function( data ) {
          $('#order-order_price').val(data.price);
        });
      } else {
        $('#order-order_price').val('');
      }
     });"
);

$this->registerJsFile(
    '@web/js/3_map.js',
    ['depends' => [\yii\web\JqueryAsset::className()]]
);
/* @var $this yii\web\View */
/* @var $model backend\models\Order */
/* @var $form yii\widgets\ActiveForm */
$customer = ArrayHelper::map(Customer::find()->select(["customer_id","CONCAT(customer_code,' - ',customer_name) as customer_name"])->all(),"customer_id","customer_name");
$unit = ArrayHelper::map(Unit::find()->all(),'p_master_unit_id','unit_name');
$listData= array(
    0 => 'Tidak',
    1 => 'Ya',
);
?>

<div class="order-form">

    <?php $form = ActiveForm::begin(); ?>
    <!--
    <?= $form->field($model, 'customer_id')->dropdownList($customer,['prompt'=>'-Customer-',
      'onchange'=>'
        $.get( "'.urldecode(Yii::$app->urlManager->createUrl('order/address/?id=')).'"+$(this).val(), function( data ) {
          $( "#address" ).text( data );
        });
    ']) ?>-->

    <?= $form->field($model, 'customer_id')->widget(Select2::classname(), [
        'data' => $customer,
        'language' => 'en',
        'options' => ['placeholder' => '-Customer-'],
        'pluginOptions' => [
            'allowClear' => true
        ],
        'pluginEvents' => [
            'change' => 'function() {
              $.get( "'.urldecode(Yii::$app->urlManager->createUrl('order/address/?id=')).'"+$(this).val(), function( data ) {
                $( "#address" ).text( data );
              });
             }',
        ],
    ]); ?>

    <?= $form->field($model, 'p_master_unit_id')->dropdownList($unit,['prompt'=>'-Unit-',
      'onchange'=>'
        $("#lat").val("");
        $("#long").val("");
        $("#order-order_date").val("");
        $.get( "'.urldecode(Yii::$app->urlManager->createUrl('order/hours/?id=')).'"+$(this).val(), function( data ) {
          $( "select#order-order_hours" ).html( data );
        });
        $.getJSON( "'.urldecode(Yii::$app->urlManager->createUrl('order/latlng/?id=')).'"+$(this).val(), function( data ) {
          //alert(data.unit_radius);
          initializeMap(data.unit_lat,data.unit_lng,data.unit_radius);
        });
    ']) ?>

    <?= $form->field($model, 'order_hours')->dropDownList(
        ['prompt'=>'-Package-']
    ); ?>

    <?= Html::activeHiddenInput($model, 'order_price'); ?>

    <?= $form->field($model, 'order_name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'order_address')->textarea(['rows' => 3,'id'=>'address']) ?>
    <!--
    <?= $form->field($model, 'order_pickup')->dropdownList($listData,['prompt'=>'-Choose-',
      'onchange'=>'
        if($(this).val() > 0 || $("#order-order_delivery").val() > 0){
          $("#map_canvas").show();
        } else {
          $("#map_canvas").hide();
        }
      ']) ?>

    <?= $form->field($model, 'order_delivery')->dropdownList($listData,['prompt'=>'-Choose-',
      'onchange'=>'
        if($(this).val() > 0 || $("#order-order_delivery").val() > 0){
          $("#map_canvas").show();
        } else {
          $("#map_canvas").hide();
        }
      ']) ?>
    -->

    <?= $form->field($model, 'order_pickup')->dropdownList($listData) ?>

    <?= $form->field($model, 'order_delivery')->dropdownList($listData) ?>

    <div id="map_canvas" style="width: 100%; height: 300px;"></div>

    <?= $form->field($model, 'order_lat')->textInput(['maxlength' => true, 'id'=>'lat','readonly' => true]) ?>

    <?= $form->field($model, 'order_lng')->textInput(['maxlength' => true, 'id'=>'long','readonly' => true]) ?>

    <?= $form->field($model, 'order_date')->widget(DatePicker::className(), [
        'language' => 'en',
        'size' => 'ms',
        'inline' => false,
        'clientOptions' => [
          'autoclose' => true,
          'format' => 'yyyy-mm-dd',
          //'todayBtn' => true
        ],
        'clientEvents' => [
          'changeDate' => '
            function (e){
              if($(\'#order-p_master_unit_id\').val() > 0){
                  $.getJSON( "'.urldecode(Yii::$app->urlManager->createUrl('order/capacity/')).'?id="+$(\'#order-p_master_unit_id\').val()+"&date="+e.format(), function( data ) {
                    if(Number(data.registered) == Number(data.capacity) && Number(data.open) > 0){
                      alert(\'Sudah Penuh Terisi \'+data.registered+\' Pendaftar, Silahkan Coba Tanggal Lain!\');
                      $("#order-order_date").val("");
                    }
                    if(Number(data.open) == 0){
                      alert(\'Libur pada hari yang dipilih, Silahkan Coba Tanggal Lain!\');
                      $("#order-order_date").val("");
                    }
                    //alert(data);
                    //initializeMap(data.unit_lat,data.unit_lng,data.unit_radius);
                  })
              } else {
                alert(\'Pilih Unit Lebih Dulu!\');
                $("#order-order_date").val("");
              }
          }'
        ]
    ]);?>

    <!--
    <?= $form->field($model, 'order_start')->widget(DateTimePicker::className(), [
        'language' => 'en',
        'size' => 'ms',
        'template' => '{input}',
        'pickButtonIcon' => 'glyphicon glyphicon-time',
        'inline' => false,
        'clientOptions' => [
            'startView' => 1,
            'minView' => 0,
            'maxView' => 1,
            'autoclose' => true,
            //'linkFormat' => 'YYYY-MM-DD HH:MM:SS', // if inline = true
            'format' => 'yyyy-mm-dd hh:ii:ss', // if inline = false
            'todayBtn' => true
        ]
    ]);?>

    <?= $form->field($model, 'order_finish')->widget(DateTimePicker::className(), [
        'language' => 'en',
        'size' => 'ms',
        'template' => '{input}',
        'pickButtonIcon' => 'glyphicon glyphicon-time',
        'inline' => false,
        'clientOptions' => [
            'startView' => 1,
            'minView' => 0,
            'maxView' => 1,
            'autoclose' => true,
            //'linkFormat' => 'YYYY-MM-DD HH:MM:SS', // if inline = true
            'format' => 'yyyy-mm-dd hh:ii:ss', // if inline = false
            'todayBtn' => true
        ]
    ]);?>
  -->
    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
