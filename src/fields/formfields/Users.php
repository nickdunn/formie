<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormFieldInterface;
use verbb\formie\base\FormFieldTrait;
use verbb\formie\base\RelationFieldTrait;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyElementFieldQueryEvent;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\Notification;

use Craft;
use craft\base\ElementInterface;
use craft\elements\User;
use craft\fields\Users as CraftUsers;
use craft\fields\data\MultiOptionsFieldData;
use craft\fields\data\OptionData;
use craft\fields\data\SingleOptionFieldData;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;

use craft\models\UserGroup;

class Users extends CraftUsers implements FormFieldInterface
{
    // Constants
    // =========================================================================

    const EVENT_MODIFY_ELEMENT_QUERY = 'modifyElementQuery';


    // Traits
    // =========================================================================

    use FormFieldTrait, RelationFieldTrait {
        getFrontEndInputOptions as traitGetFrontendInputOptions;
        getEmailHtml as traitGetEmailHtml;
        getSavedFieldConfig as traitGetSavedFieldConfig;
        RelationFieldTrait::getIsFieldset insteadof FormFieldTrait;
    }


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Users');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/users/icon.svg';
    }


    // Properties
    // =========================================================================

    /**
     * @var bool
     */
    public $searchable = true;

    /**
     * @var string
     */
    protected $inputTemplate = 'formie/_includes/element-select-input';

    /**
     * @var UserGroup
     */
    private $_userGroup;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function __construct()
    {
         $this->labelSource = 'fullName';
    }

    /**
     * @inheritDoc
     */
    public function getSavedFieldConfig(): array
    {
        $settings = $this->traitGetSavedFieldConfig();

        return $this->modifyFieldSettings($settings);
    }

    /**
     * @inheritDoc
     */
    public function getExtraBaseFieldConfig(): array
    {
        $options = $this->getSourceOptions();

        return [
            'sourceOptions' => $options,
            'warning' => count($options) < 2 ? Craft::t('formie', 'No user groups available. View [user group settings]({link}).', ['link' => UrlHelper::cpUrl('settings/users') ]) : false,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        return [
            'sources' => '*',
            'placeholder' => Craft::t('formie', 'Select a user'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getDefaultValue($attributePrefix = '')
    {
        return $this->getDefaultValueQuery();
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/users/preview', [
            'field' => $this
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndInputOptions(Form $form, $value, array $options = null): array
    {
        $inputOptions = $this->traitGetFrontendInputOptions($form, $value, $options);
        $inputOptions['usersQuery'] = $this->getElementsQuery();

        return $inputOptions;
    }

    /**
     * @inheritDoc
     */
    public function getEmailHtml(Submission $submission, Notification $notification, $value, array $options = null)
    {
        // Ensure we return back the correct, prepped query for emails. Just as we would be submissions.
        $value = $this->_all($value, $submission);

        return $this->traitGetEmailHtml($submission, $notification, $value, $options);
    }

    /**
     * Returns the list of selectable users.
     *
     * @return User[]
     */
    public function getElementsQuery()
    {
        $query = User::find();

        if ($this->sources !== '*') {
            $criteria = [];

            // Try to find the criteria we're restricting by - if any
            foreach ($this->sources as $source) {
                $elementSource = ArrayHelper::firstWhere($this->availableSources(), 'key', $source);
                $elementCriteria = $elementSource['criteria'] ?? [];

                $criteria = array_merge_recursive($criteria, $elementCriteria);
            }

            // Apply the criteria on our query
            Craft::configure($query, $criteria);
        }

        $query->limit($this->limit);

        $query->orderBy('title ASC');

        // Fire a 'modifyElementFieldQuery' event
        $event = new ModifyElementFieldQueryEvent([
            'query' => $query,
            'field' => $this,
        ]);
        $this->trigger(self::EVENT_MODIFY_ELEMENT_QUERY, $event);

        return $event->query;
    }

    /**
     * @inheritDoc
     */
    public function defineLabelSourceOptions()
    {
        $options = [
            ['value' => 'username', 'label' => Craft::t('app', 'Username')],
            ['value' => 'email', 'label' => Craft::t('app', 'Email')],
            ['value' => 'firstName', 'label' => Craft::t('app', 'First Name')],
            ['value' => 'lastName', 'label' => Craft::t('app', 'Last Name')],
            ['value' => 'fullName', 'label' => Craft::t('app', 'Full Name')],
        ];

        if ($fieldLayout = Craft::$app->getFields()->getLayoutByType(User::class)) {
            $fields = $this->getStringCustomFieldOptions($fieldLayout->getFields());

            $options = array_merge($options, $fields);
        }

        return $options;
    }

    /**
     * @inheritDoc
     */
    public function defineGeneralSchema(): array
    {
        $options = $this->getSourceOptions();

        return [
            SchemaHelper::labelField(),
            SchemaHelper::toggleContainer('settings.displayType=dropdown', [
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Placeholder'),
                    'help' => Craft::t('formie', 'The option shown initially, when no option is selected.'),
                    'name' => 'placeholder',
                    'validation' => 'required',
                    'required' => true,
                ]),
            ]),
            SchemaHelper::checkboxSelectField([
                'label' => Craft::t('formie', 'Sources'),
                'help' => Craft::t('formie', 'Which sources do you want to select users from?'),
                'name' => 'sources',
                'options' => $options,
                'showAllOption' => true,
                'validation' => 'required',
                'required' => true,
                'element-class' => count($options) < 2 ? 'hidden' : false,
                'warning' => count($options) < 2 ? Craft::t('formie', 'No user groups available. View [user group settings]({link}).', ['link' => UrlHelper::cpUrl('settings/users') ]) : false,
            ]),
            SchemaHelper::elementSelectField([
                'label' => Craft::t('formie', 'Default Value'),
                'help' => Craft::t('formie', 'Select a default user to be selected.'),
                'name' => 'defaultValue',
                'selectionLabel' => $this->defaultSelectionLabel(),
                'config' => [
                    'jsClass' => $this->inputJsClass,
                    'elementType' => static::elementType(),
                ],
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineSettingsSchema(): array
    {
        $labelSourceOptions = $this->getLabelSourceOptions();
        
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Required Field'),
                'help' => Craft::t('formie', 'Whether this field should be required when filling out the form.'),
                'name' => 'required',
            ]),
            SchemaHelper::toggleContainer('settings.required', [
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Error Message'),
                    'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
                    'name' => 'errorMessage',
                ]),
            ]),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Limit'),
                'help' => Craft::t('formie', 'Limit the number of selectable users.'),
                'name' => 'limit',
                'size' => '3',
                'class' => 'text',
                'validation' => 'optional|number|min:0',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Label Source'),
                'help' => Craft::t('formie', 'Select what to use as the label for each entry.'),
                'name' => 'labelSource',
                'options' => $labelSourceOptions,
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Display Type'),
                'help' => Craft::t('formie', 'Set different display layouts for this field.'),
                'name' => 'displayType',
                'options' => [
                    [ 'label' => Craft::t('formie', 'Dropdown'), 'value' => 'dropdown' ],
                    [ 'label' => Craft::t('formie', 'Checkboxes'), 'value' => 'checkboxes' ],
                    [ 'label' => Craft::t('formie', 'Radio Buttons'), 'value' => 'radio' ],
                ],
            ]),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::inputAttributesField(),
        ];
    }
}
