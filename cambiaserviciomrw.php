<?php
/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Cambiaserviciomrw extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'cambiaserviciomrw';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Sergio';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Cambia servicio MRW');
        $this->description = $this->l('El módulo de MRW no permite asignar los servicios de forma automática dependiendo del destino. El módulo analizará cada nuevo order carrier y si el transportista es MRW asignará el servicio según el destino.');

        $this->confirmUninstall = $this->l('¿Me vas a desinstalar?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('CAMBIASERVICIOMRW_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&//hook que se llama después de la creación del objeto OrderCarrier
            $this->registerHook('actionObjectOrderCarrierAddAfter');
    }

    public function uninstall()
    {
        Configuration::deleteByName('CAMBIASERVICIOMRW_LIVE_MODE');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitCambiaserviciomrwModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCambiaserviciomrwModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Asignar servicio MRW correcto a los pedidos según destino. Bag 14 a España, Bag 19 a Portugal'),
                        'name' => 'CAMBIASERVICIOMRW_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Cambiar servicio'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),                    
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'CAMBIASERVICIOMRW_LIVE_MODE' => Configuration::get('CAMBIASERVICIOMRW_LIVE_MODE'),            
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    // public function hookHeader()
    // {
    //     $this->context->controller->addJS($this->_path.'/views/js/front.js');
    //     $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    // }


    // $params contiene el objeto orderCarrier en este caso, $params['object'], es decir, $params['object']->id sería el id de la tabla order_carrier
    // tenemos que comprobar que el pedido tiene transportista MRW, si es así hay que comprobar el destino. El módulo está configurado con servicio Bag 14 por defecto, que queremos utilizar para España, de modo que si el destino es Portugal haremos el cambio a servicio Bag 19 
    public function hookActionObjectOrderCarrierAddAfter($params) 
    {
        //Comprobamos la configuración del módulo, si está activo para el cambio de servicio
        if (!Configuration::get('CAMBIASERVICIOMRW_LIVE_MODE')){
            return;
        }

        //El hook funcionará cada vez que entra un nuevo pedido.
        if ($params) {            

            //sacamos el objeto order_carrier
            $order_carrier = $params['object'];
            if (Validate::isLoadedObject($order_carrier))
            {       
                //primero aseguramos de que el transportista es MRW. Obtenemos el id_carrier de MRW
                $id_mrw = (int)Configuration::get('MRWCARRIER_CARRIER_ID_MRW');

                //sacamos el id_carrier del pedido entrante
                $id_carrier = (int)$order_carrier->id_carrier;     
                
                if ($id_mrw != $id_carrier) {
                    //no es MRW
                    return;
                }

                //es MRW, instanciamos order para obtener su dirección de entrega
                $id_order = (int)$order_carrier->id_order;

                $order = new Order($id_order);

                if (!Validate::isLoadedObject($order)) {
                    return;
                }               

                //sacamos el país de destino desde la dirección, nos interesa España, 6 o Portugal 15. En otros casos sería internacional etc, pero no hemos configurado los transportistas  más que para La Rioja , península y Portugal.
                $id_address = $order->id_address_delivery;
                //instanciamos dirección para sacar el id_country
                $address = new Address($id_address);
                if (!Validate::isLoadedObject($address)) {
                    return;
                }
                $id_country = $address->id_country;

                if ($id_country != 6 && $id_country != 15) {
                    //no es España ni Portugal
                    return;
                }

                //obtenemos servicio por defecto, que es el que tiene en este punto
                $default_service = Configuration::get('MRWCARRIER_SERVICE_MRW');

                //en este punto solo puede tenr destino España o Portugal
                if ($id_country == 6) {
                    //España, aseguramos que tenga servicio 0235
                    if ($default_service == '0235') {
                        //el servicio es el correcto
                        return;
                    } else {
                        //tenemos que insertar el pedido en mrwcarrier_mrw. Sacamos el id de suscriptor para el servicio 0235
                        $sql_id_subscriber = 'SELECT id_subscriber FROM lafrips_mrwcarrier_subs 
                            WHERE service = "0235"
                            AND environment = "mrw_pro"';
                        if (!$id_subscriber = Db::getInstance()->getValue($sql_id_subscriber)) {
                            //el servicio 0235 no está configurado
                            return;
                        }

                        $date = date('Y-m-d');

                        //insertamos el pedido en mrwcarrier_mrw asignándole 0235
                        $sql_insert_mrwcarrier_mrw = "INSERT INTO lafrips_mrwcarrier_mrw
                        (id_shop, order_id, `date`, cant, subscriber, `service`, saturday, agency, backReturn, mrw_warehouse, mrw_slot)
                        VALUES 
                        (1, $id_order, '$date', 1, '$id_subscriber', '0235', 0, 0, 0, 0, 0)";

                        Db::getInstance()->Execute($sql_insert_mrwcarrier_mrw);

                        return;
                    }

                } else {
                    //Portugal, aseguramos servicio 0230
                    if ($default_service == '0230') {
                        //el servicio es el correcto
                        return;
                    } else {
                        //tenemos que insertar el pedido en mrwcarrier_mrw. Sacamos el id de suscriptor para el servicio 0230
                        $sql_id_subscriber = 'SELECT id_subscriber FROM lafrips_mrwcarrier_subs 
                            WHERE service = "0230"
                            AND environment = "mrw_pro"';
                        if (!$id_subscriber = Db::getInstance()->getValue($sql_id_subscriber)) {
                            //el servicio 0230 no está configurado
                            return;
                        }

                        $date = date('Y-m-d');

                        //insertamos el pedido en mrwcarrier_mrw asignándole 0235
                        $sql_insert_mrwcarrier_mrw = "INSERT INTO lafrips_mrwcarrier_mrw
                        (id_shop, order_id, `date`, cant, subscriber, `service`, saturday, agency, backReturn, mrw_warehouse, mrw_slot)
                        VALUES 
                        (1, $id_order, '$date', 1, '$id_subscriber', '0230', 0, 0, 0, 0, 0)";

                        Db::getInstance()->Execute($sql_insert_mrwcarrier_mrw);

                        return;
                    }

                }   
             
            }
        }
    }
}
