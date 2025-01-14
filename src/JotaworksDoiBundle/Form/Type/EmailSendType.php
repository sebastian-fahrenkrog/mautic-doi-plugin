<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\JotaworksDoiBundle\Form\Type;

use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Mautic\CoreBundle\Form\Type\ButtonGroupType;
use Mautic\EmailBundle\Form\Type\EmailListType;
use Mautic\LeadBundle\Form\Type\TagType;
use Mautic\LeadBundle\Form\Type\LeadListType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Url;

/**
 * Class EmailSendType.
 */
class EmailSendType extends AbstractType
{
    protected $factory;

    /**
     * @var TranslatorInterface
     */
    private $translator;    

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory, TranslatorInterface $translator)
    {
        $this->factory = $factory;
        $this->translator = $translator;

    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'email',
            EmailListType::class,
            [
                'label'      => 'mautic.email.send.selectemails',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.email.choose.emails_descr',
                    'onchange' => 'Mautic.disabledEmailAction(window, this)',
                ],
                'multiple'    => false,
                'required'    => true,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.email.chooseemail.notblank']
                    ),
                ],
            ]
        );

        if (!empty($options['with_email_types'])) {
            $builder->add(
                'email_type',
                ButtonGroupType::class,
                [
                    'choices' => [
                        'transactional' => 'mautic.email.send.emailtype.transactional',
                        'marketing'     => 'mautic.email.send.emailtype.marketing',
                    ],
                    'label'      => 'mautic.email.send.emailtype',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control email-type',
                        'tooltip' => 'mautic.email.send.emailtype.tooltip',
                    ],
                    'data' => (!isset($options['data']['email_type'])) ? 'transactional' : $options['data']['email_type'],
                ]
            );
        }

        if (!empty($options['update_select'])) {
            $windowUrl = $this->factory->getRouter()->generate(
                'mautic_email_action',
                [
                    'objectAction' => 'new',
                    'contentOnly'  => 1,
                    'updateSelect' => $options['update_select'],
                ]
            );

            $builder->add(
                'newEmailButton',
                ButtonType::class,
                [
                    'attr' => [
                        'class'   => 'btn btn-primary btn-nospin',
                        'onclick' => 'Mautic.loadNewWindow({
                        "windowUrl": "'.$windowUrl.'"
                    })',
                        'icon' => 'fa fa-plus',
                    ],
                    'label' => 'mautic.email.send.new.email',
                ]
            );

            // create button edit email
            $windowUrlEdit = $this->factory->getRouter()->generate(
                'mautic_email_action',
                [
                    'objectAction' => 'edit',
                    'objectId'     => 'emailId',
                    'contentOnly'  => 1,
                    'updateSelect' => $options['update_select'],
                ]
            );

            $builder->add(
                'editEmailButton',
                ButtonType::class,
                [
                    'attr' => [
                        'class'    => 'btn btn-primary btn-nospin',
                        'onclick'  => 'Mautic.loadNewWindow(Mautic.standardEmailUrl({"windowUrl": "'.$windowUrlEdit.'","origin":"#'.$options['update_select'].'"}))',
                        'disabled' => !isset($options['data']['email']),
                        'icon'     => 'fa fa-edit',
                    ],
                    'label' => 'mautic.email.send.edit.email',
                ]
            );

            // create button preview email
            $windowUrlPreview = $this->factory->getRouter()->generate('mautic_email_preview', ['objectId' => 'emailId']);

            $builder->add(
                'previewEmailButton',
                ButtonType::class,
                [
                    'attr' => [
                        'class'    => 'btn btn-primary btn-nospin',
                        'onclick'  => 'Mautic.loadNewWindow(Mautic.standardEmailUrl({"windowUrl": "'.$windowUrlPreview.'","origin":"#'.$options['update_select'].'"}))',
                        'disabled' => !isset($options['data']['email']),
                        'icon'     => 'fa fa-external-link',
                    ],
                    'label' => 'mautic.email.send.preview.email',
                ]
            );
            if (!empty($options['with_email_types'])) {
                $data = (!isset($options['data']['priority'])) ? 2 : (int) $options['data']['priority'];
                $builder->add(
                    'priority',
                     ChoiceType::class,
                    [
                        'choices' => [
                            MessageQueue::PRIORITY_NORMAL => 'mautic.channel.message.send.priority.normal',
                            MessageQueue::PRIORITY_HIGH   => 'mautic.channel.message.send.priority.high',
                        ],
                        'label'    => 'mautic.channel.message.send.priority',
                        'required' => false,
                        'attr'     => [
                            'class'        => 'form-control',
                            'tooltip'      => 'mautic.channel.message.send.priority.tooltip',
                            'data-show-on' => '{"campaignevent_properties_email_type_1":"checked"}',
                        ],
                        'data'        => $data,
                        'empty_value' => false,
                    ]
                );

                $data = (!isset($options['data']['attempts'])) ? 3 : (int) $options['data']['attempts'];
                $builder->add(
                    'attempts',
                    NumberType::class,
                    [
                        'label' => 'mautic.channel.message.send.attempts',
                        'attr'  => [
                            'class'        => 'form-control',
                            'tooltip'      => 'mautic.channel.message.send.attempts.tooltip',
                            'data-show-on' => '{"campaignevent_properties_email_type_1":"checked"}',
                        ],
                        'data'       => $data,
                        'empty_data' => 0,
                        'required'   => false,
                    ]
                );
            }

            $builder->add(
                'add_campaign_doi_success_tags',
                TagType::class,
                [
                    'label' => 'jw.mautic.lead.tags.add_campaign_doi_success_tags',
                    'attr'  => [
                        'data-placeholder'     => $this->translator->trans('mautic.lead.tags.select_or_create'),
                        'data-no-results-text' => $this->translator->trans('mautic.lead.tags.enter_to_create'),
                        'data-allow-add'       => 'true',
                        'onchange'             => 'Mautic.createLeadTag(this)',
                    ],
                    'data'            => (isset($options['data']['add_campaign_doi_success_tags'])) ? $options['data']['add_campaign_doi_success_tags'] : null,
                    'add_transformer' => true,
                ]
            );
              
            $builder->add(
                'remove_tags_doi_success_tags',
                TagType::class,
                [
                    'label' => 'jw.remove_tags_doi_success_tags',
                    'attr'  => [
                        'data-placeholder'     => $this->translator->trans('mautic.lead.tags.select_or_create'),
                        'data-no-results-text' => $this->translator->trans('mautic.lead.tags.enter_to_create'),
                        'data-allow-add'       => 'true',
                        'onchange'             => 'Mautic.createLeadTag(this)',
                    ],
                    'data'            => (isset($options['data']['remove_tags_doi_success_tags'])) ? $options['data']['remove_tags_doi_success_tags'] : null,
                    'add_transformer' => true,
                ]
            );          
            
            $builder->add('add_campaign_doi_success_lists', LeadListType::class, [
                'label'      => 'jw.add_campaign_doi_success_lists',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'multiple' => true,
                'expanded' => false,
            ]);
    
            $builder->add('remove_campaign_doi_success_lists', LeadListType::class, [
                'label'      => 'jw.remove_campaign_doi_success_lists',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'multiple' => true,
                'expanded' => false,
            ]);            
            
            $builder->add(
                'post_url',
                UrlType::class,
                [
                    'label'      => 'jw.mautic.form.action.redirect_url',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'    => 'form-control',
                        'preaddon' => 'fa fa-globe',
                    ],
                    'constraints' => [
                        new NotBlank(
                            [
                                'message' => 'mautic.core.value.required',
                            ]
                        ),
                        new Url(
                            [
                                'message' => 'mautic.core.valid_url_required',
                            ]
                        ),
                    ],
                ]
            );            
    
           
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'with_email_types' => false,
            ]
        );

        $resolver->setDefined(['update_select', 'with_email_types']);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'jw.mautic.form.type.jw_emailsend_list';
    }
}
