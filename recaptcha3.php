<?php
/**
* 2007-2023 PrestaShop
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
*  @copyright 2007-2023 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Recaptcha3 extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'recaptcha3';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Vallka';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Recaptcha3');
        $this->description = $this->l('Recaptcha3 uses Google recaptcha v3');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('RECAPTCHA3_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('DisplayBeforeBodyClosingTag') &&
            $this->registerHook('displayNewsletterRegistration') &&
            $this->registerHook('actionNewsletterRegistrationBefore')
        ;
    }

    public function uninstall()
    {
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
        if (((bool)Tools::isSubmit('submitRecaptcha3Module')) == true) {
            $this->postProcess();
        }

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
        $helper->submit_action = 'submitRecaptcha3Module';
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
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('reCaptcha v3 Configuration'),
					'icon' => 'icon-cogs'
				),
				'description' => $this->l('To get your own public and private keys please click on the folowing link').'<br /><a href="https://www.google.com/recaptcha/intro/index.html" target="_blank">https://www.google.com/recaptcha/intro/index.html</a>',
				'input' => array(
					array(
						'type' => 'text',
						'label' => $this->l('reCaptcha public key'),
						'name' => 'RECAPTCHA3_PUBLIC_KEY',
						'required' => true,
						'empty_message' => $this->l('Please fill the captcha public key'),
					),
					array(
						'type' => 'text',
						'label' => $this->l('reCaptcha private key'),
						'name' => 'RECAPTCHA3_PRIVATE_KEY',
						'required' => true,
						'empty_message' => $this->l('Please fill the captcha private key'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Newsletter'),
                        'name' => 'RECAPTCHA3_NEWSLETTER',
                        'is_bool' => true,
                        'desc' => $this->l('Use reCaptcha in Newsletter Subscription'),
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
					array(
						'type' => 'text',
						'label' => $this->l('Threshold for Newsletter'),
						'name' => 'RECAPTCHA3_NEWSLETTER_THRESHOLD',
						'required' => true,
						'desc' => $this->l('Float value betwwen 0 and 1. 0 - bot, 1 - real person. Default: 0.5'),
                    ),


                    


                ),
            'submit' => array(
					'title' => $this->l('Save'),
					'class' => 'button btn btn-default pull-right',
				)
			),
		);

        return $fields_form;
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
		return array(
			'RECAPTCHA3_PUBLIC_KEY' => Tools::getValue('RECAPTCHA3_PUBLIC_KEY', Configuration::get('RECAPTCHA3_PUBLIC_KEY')),
			'RECAPTCHA3_PRIVATE_KEY' => Tools::getValue('RECAPTCHA3_PRIVATE_KEY', Configuration::get('RECAPTCHA3_PRIVATE_KEY')),
            'RECAPTCHA3_NEWSLETTER' => Configuration::get('RECAPTCHA3_NEWSLETTER', true),
			'RECAPTCHA3_NEWSLETTER_THRESHOLD' => Tools::getValue('RECAPTCHA3_NEWSLETTER_THRESHOLD', 0.5),
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

    public function hookDisplayBeforeBodyClosingTag()
	{
        //PrestaShopLogger::addLog('hookDisplayBeforeBodyClosingTag');



        $controller = $this->context->controller->php_self;

        $html=<<<EOT

        <script src="https://www.google.com/recaptcha/api.js"></script>
        <script>
            function onSubmit(token) {
                document.getElementById("demo-form").submit();
            }
        </script>
        <!-- 
        controller: 
        $controller
        ----
        <button class="g-recaptcha" 
            data-sitekey="reCAPTCHA_site_key" 
            data-callback='onSubmit' 
            data-action='submit'>Submit</button>
        -->    
EOT;

		return '';
	}

    public function hookDisplayNewsletterRegistration()
    {
        if (Configuration::get('RECAPTCHA3_NEWSLETTER')) {
            //PrestaShopLogger::addLog("hookDisplayNewsletterRegistration");
            $action = 'newsletter';
            $site_key = Configuration::get('RECAPTCHA3_PUBLIC_KEY');
            $html=<<<EOT
            <!-- hookDisplayNewsletterRegistration -->   
            <style>
            .grecaptcha-badge {
                display: none !important;
              }
            </style>
            <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallbackGC&render=$site_key" async defer></script>
            <script>
                document.querySelector('.email_subscription form input[name=submitNewsletter]').style.display = 'none';
                
                function wait4recaptcha() {
                    if (!document.getElementById('id_recapture3token').value) {
                        grecaptcha.execute('$site_key', { action: '$action' }).then(
                            function(token) {
                                document.getElementById('id_recapture3token').value = token;
                            }
                        )
                        setTimeout(function(){
                            document.getElementById('id_recapture3token').value = null;
                            wait4recaptcha();

                        },60000);
                    }
                }

                var onloadCallbackGC = function() {
                    var token_input = document.createElement("input");
                    token_input.type = "hidden";
                    token_input.name = "recapture3token";
                    token_input.value = null;
                    token_input.id = "id_recapture3token";
                    document.querySelector('.email_subscription form').appendChild(token_input);
                    document.querySelector('.email_subscription form input[name=submitNewsletter]').style.display = '';

                    document.querySelector('.email_subscription form input[name=email]').addEventListener("focus",function() {
                        wait4recaptcha();
                    });
                    
                };
            </script>
EOT;

    		return $html;

        }
        return '';
    }


    public function hookActionNewsletterRegistrationBefore($par)
    {
        if (Configuration::get('RECAPTCHA3_NEWSLETTER')) {
            $post = var_export($_POST,1);
            PrestaShopLogger::addLog("hookActionNewsletterRegistrationBefore:{$_SERVER['REMOTE_ADDR']}:$post");
            $data = array(
                'secret' => Configuration::get('RECAPTCHA3_PRIVATE_KEY'),
                'response' => $_POST['recapture3token'],
                'remoteip' => $_SERVER['REMOTE_ADDR']
            );
            $verify = curl_init();
            if(isset($verify) && $verify){
                curl_setopt($verify, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
                curl_setopt($verify, CURLOPT_POST, true);
                curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
                curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
                $response = @curl_exec($verify);

                PrestaShopLogger::addLog($response);

                $decode = json_decode($response, true);

                if (!$decode['success'] == true) {
                    $par['hookError'] = join(' ',$decode["error-codes"]);
                    return join(' ',$decode["error-codes"]);
                }
                if ($decode['score'] < Configuration::get('RECAPTCHA3_NEWSLETTER_THRESHOLD')) {
                    $par['hookError'] = $this->l('reCaptcha failed');
                    return $par['hookError'];
                }

                return null;

            }
            else {
                $par['hookError'] = 'reCaptcha call error';
                return $par['hookError'];
            }
        }
        return null;
    }
}
