<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CvsFormStructureService
{
    public function __construct(
        private ParameterBagInterface $parameterBag
    ) {}

    private function getTranslations(string $locale): array
    {
        $translations = [
            'en' => [
                'full_name' => 'Full Name',
                'full_name_placeholder' => 'Full Name Here...',
                'mobile_number' => 'Mobile Number',
                'mobile_placeholder' => 'E.g. 127654321',
                'email' => 'Email',
                'email_placeholder' => 'Email Here...',
                'nric_passport' => 'NRIC/Passport',
                'ic_no' => 'NRIC',
                'passport' => 'Passport',
                'upload_receipt' => 'Upload Receipt',
                'receipt_number' => 'Receipt Number',
                'receipt_placeholder' => 'Input Receipt Number Here...',
                'channel' => 'Channel',
                'proof_purchase' => 'All entries must be accompanied by valid proof of purchase.',
                'privacy_policy' => 'I have read and agree to the <a href=\'https://carlsbergmalaysia.com.my/privacy-policy/\' target=\'_blank\' class=\'\' style=\'color:white !important\'>privacy policy.</a>',
                'terms_conditions' => 'By participating in the contest you agree to abide by the <a href=\'https://bestwithcarlsberg.my/download/pdfs/CNY_2026_TNC.pdf?v=2\' target=\'_blank\' class=\'\' style=\'color:white !important\'>Terms & Conditions</a> of this promotion. The Organizer reserves the right to change or amend any terms relating to this Promotions.'
                #'terms_conditions' => 'By participating in the contest you agree to abide by the <a href=\'https://bestwithcarslberg.my/download/pdfs/cny2026TNC.pdf\' target=\'_blank\' style=\'color:white !important\'>Terms & Conditions</a> of this promotion. The Organizer reserves the right to change or amend any terms relating to this Promotions.'

            ],
            'ch' => [
                'full_name' => '全名',
                'full_name_placeholder' => '请输入全名...',
                'mobile_number' => '手机号码',
                'mobile_placeholder' => '例如：127654321',
                'email' => '电子邮箱',
                'email_placeholder' => '请输入电子邮箱...',
                'nric_passport' => '身份证/护照',
                'ic_no' => '身份证号码',
                'passport' => '护照',
                'upload_receipt' => '上传收据',
                'receipt_number' => '收据号码',
                'receipt_placeholder' => '请输入收据号码...',
                'channel' => '渠道',
                'proof_purchase' => '每个提交都必须附上有效的购买收据。​',
                'privacy_policy' => '我已阅读并同意<a href=\'https://carlsbergmalaysia.com.my/privacy-policy/\' target=\'_blank\' class=\'\' style=\'color:white !important\'>隐私政策</a>。',
                'terms_conditions' => '参与本次活动即表示您同意遵守本活动的<a href=\'https://bestwithcarlsberg.my/download/pdfs/CNY_2026_TNC.pdf?v=2\' target=\'_blank\' class=\'\' style=\'color:white !important\'>条款和条件</a>。主办方保留随时更改或修改本活动相关条款与条件的权利。'
            ]
        ];
        $proxyUrl = $this->parameterBag->get('app.proxy_url');
        if ( !str_contains($proxyUrl, "bestwithcarlsberg.my") ) {
              foreach ($translations as &$lng) {
                if (isset($lng['terms_conditions'])) {
                    $lng['terms_conditions'] = str_replace(
                        'https://bestwithcarlsberg.my/download/pdfs/',
                        '/build/pdfs/',
                        $lng['terms_conditions']
                    );
                }
            }
            unset($lng);
        } 
        return $translations[$locale] ?? $translations['en'];
    }

    public function getFormStructure(string $locale = 'en'): array
    {
        $t = $this->getTranslations($locale);
        return [
            'form_group' => [
                [
                    'index' => 0,
                    'name' => 'full_name',
                    'label' => $t['full_name'],
                    'placeholder' => $t['full_name_placeholder'],
                    'value' => '',
                    'custom_message' => '',
                    'maxlength' => 150,
                    'prefix_value' => '',
                    'required' => true,
                    'component' => 'input',
                    'type' => 'text',
                    'disabled' => false,
                    'mask' => '',
                    'group' => 'form_group',
                    'validation' => '',
                    'custom_class' => '',
                    'toggle' => false,
                    'toggleKB' => false,
                    'search' => '',
                    'search_message' => '',
                    'error_message' => '',
                    'data_url' => '',
                    'options' => null,
                    'alpine_model' => 'full_name'
                ],
                [
                    'index' => 1,
                    'name' => 'mobile_no',
                    'label' => $t['mobile_number'],
                    'placeholder' => $t['mobile_placeholder'],
                    'value' => '',
                    'custom_message' => '',
                    'maxlength' => 11,
                    'prefix_value' => '60',
                    'required' => true,
                    'component' => 'mobile-prefix',
                    'type' => 'text',
                    'disabled' => false,
                    'mask' => '9999999999',
                    'group' => 'cvs_form_group',
                    'validation' => '',
                    'custom_class' => '',
                    'toggle' => false,
                    'toggleKB' => false,
                    'search' => '',
                    'search_message' => '',
                    'error_message' => '',
                    'data_url' => '',
                    'options' => [
                        ['label' => '+60', 'value' => '60']
                    ],
                    'alpine_model' => 'mobile_no',
                    'prefix_alpine_model' => 'mobile_prefix'
                ],
                [
                    'index' => 2,
                    'name' => 'email',
                    'label' => $t['email'],
                    'placeholder' => $t['email_placeholder'],
                    'value' => '',
                    'custom_message' => '',
                    'maxlength' => 256,
                    'prefix_value' => '',
                    'required' => true,
                    'component' => 'input',
                    'type' => 'email',
                    'disabled' => false,
                    'mask' => '',
                    'group' => 'form_group',
                    'validation' => '',
                    'custom_class' => '',
                    'toggle' => false,
                    'toggleKB' => false,
                    'search' => '',
                    'search_message' => '',
                    'error_message' => '',
                    'data_url' => '',
                    'options' => null,
                    'alpine_model' => 'email'
                ],
                [
                    'index' => 3,
                    'name' => 'national_id',
                    'label' => $t['nric_passport'],
                    'placeholder' => 'e.g. 999999-99-9999',
                    'value' => '',
                    'custom_message' => '',
                    'maxlength' => '',
                    'prefix_value' => 'NRIC',
                    'required' => true,
                    'component' => 'nricppt',
                    'type' => 'text',
                    'disabled' => false,
                    'mask' => '999999-99-9999',
                    'group' => 'cvs_form_group',
                    'validation' => '',
                    'custom_class' => '',
                    'toggle' => false,
                    'toggleKB' => false,
                    'search' => '',
                    'search_message' => '',
                    'error_message' => '',
                    'data_url' => '',
                    'options' => [
                        [
                            'label' => $t['ic_no'],
                            'value' => 'NRIC',
                            'mask' => '999999-99-9999',
                            'placeholder' => 'e.g. 999999-99-9999'
                        ],
                        [
                            'label' => $t['passport'],
                            'value' => 'PASSPORT',
                            'mask' => '',
                            'placeholder' => 'e.g. A987654321'
                        ]
                    ],
                    'alpine_model' => 'national_id',
                    'prefix_alpine_model' => 'nric_prefix'
                ],
                [
                    'index' => 4,
                    'name' => 'upload_receipt',
                    'label' => $t['upload_receipt'],
                    'placeholder' => 'Upload Receipt Here...',
                    'value' => '',
                    'custom_message' => '',
                    'maxlength' => '',
                    'prefix_value' => '',
                    'required' => true,
                    'component' => 'file-upload',
                    'type' => 'file',
                    'disabled' => false,
                    'mask' => '',
                    'group' => 'form_group',
                    'validation' => '',
                    'custom_class' => '',
                    'toggle' => false,
                    'toggleKB' => false,
                    'search' => '',
                    'search_message' => '',
                    'error_message' => '',
                    'data_url' => '',
                    'helper' => false,
                    'options' => null,
                    'alpine_model' => 'upload_receipt'
                ],
                // [
                //     'index' => 5,
                //     'name' => 'receipt_no',
                //     'label' => $t['receipt_number'],
                //     'placeholder' => $t['receipt_placeholder'],
                //     'value' => '',
                //     'custom_message' => '',
                //     'maxlength' => 30,
                //     'prefix_value' => '',
                //     'required' => true,
                //     'component' => 'input',
                //     'type' => 'text',
                //     'disabled' => false,
                //     'mask' => '',
                //     'group' => 'form_group',
                //     'validation' => '',
                //     'custom_class' => '',
                //     'toggle' => false,
                //     'toggleKB' => false,
                //     'search' => '',
                //     'search_message' => '',
                //     'error_message' => '',
                //     'data_url' => '',
                //     'helper' => true,
                //     'options' => null,
                //     'alpine_model' => 'receipt_no'
                // ],
                [
                    'index' => 5,
                    'name' => 'channel_name',
                    'label' => $t['channel'],
                    'placeholder' => ' Here...',
                    'value' => '',
                    'custom_message' => '',
                    'maxlength' => 25,
                    'prefix_value' => '',
                    'required' => false,
                    'component' => 'input',
                    'type' => 'text',
                    'disabled' => true,
                    'mask' => '',
                    'group' => 'form_group',
                    'validation' => '',
                    'custom_class' => '',
                    'toggle' => false,
                    'toggleKB' => false,
                    'search' => '',
                    'search_message' => '',
                    'error_message' => '',
                    'data_url' => '',
                    'options' => null,
                    'alpine_model' => 'channel_name'
                ],
                [
                    'index' => 6,
                    'name' => 'form_code',
                    'label' => '',
                    'placeholder' => ' Here...',
                    'value' => 'CVSTOFT',
                    'custom_message' => '',
                    'maxlength' => 25,
                    'prefix_value' => '',
                    'required' => false,
                    'component' => 'hidden',
                    'type' => 'text',
                    'disabled' => false,
                    'mask' => '',
                    'group' => 'form_group',
                    'validation' => '',
                    'custom_class' => '',
                    'toggle' => false,
                    'toggleKB' => false,
                    'search' => '',
                    'search_message' => '',
                    'error_message' => '',
                    'data_url' => '',
                    'options' => null,
                    'alpine_model' => 'form_code'
                ]
            ],
            'checkbox_group' => [
                [
                    'index' => 0,
                    'name' => 'label',
                    'label' => $t['proof_purchase'],
                    'placeholder' => '',
                    'value' => '',
                    'custom_message' => '',
                    'maxlength' => 30,
                    'prefix_value' => '',
                    'required' => false,
                    'component' => 'label',
                    'type' => 'text',
                    'disabled' => false,
                    'mask' => '',
                    'group' => 'checkbox_group',
                    'validation' => '',
                    'custom_class' => '',
                    'toggle' => false,
                    'toggleKB' => false,
                    'search' => '',
                    'search_message' => '',
                    'error_message' => '',
                    'data_url' => '',
                    'options' => null
                ],
                [
                    'index' => 1,
                    'name' => 'privacy',
                    'label' => $t['privacy_policy'],
                    'placeholder' => '',
                    'value' => '',
                    'custom_message' => '',
                    'maxlength' => 30,
                    'prefix_value' => '',
                    'required' => true,
                    'component' => 'checkbox',
                    'type' => 'checkbox',
                    'disabled' => false,
                    'mask' => '',
                    'group' => 'checkbox_group',
                    'validation' => '',
                    'custom_class' => '',
                    'toggle' => false,
                    'toggleKB' => false,
                    'search' => '',
                    'search_message' => '',
                    'error_message' => '',
                    'data_url' => '',
                    'options' => null
                ],
                [
                    'index' => 2,
                    'name' => 'tnc',
                    'label' => $t['terms_conditions'],
                    'placeholder' => '',
                    'value' => '',
                    'custom_message' => '',
                    'maxlength' => 30,
                    'prefix_value' => '',
                    'required' => true,
                    'component' => 'checkbox',
                    'type' => 'checkbox',
                    'disabled' => false,
                    'mask' => '',
                    'group' => 'checkbox_group',
                    'validation' => '',
                    'custom_class' => '',
                    'toggle' => false,
                    'toggleKB' => false,
                    'search' => '',
                    'search_message' => '',
                    'error_message' => '',
                    'data_url' => '',
                    'options' => null
                ]
            ]
        ];
    }
}