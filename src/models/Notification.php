<?php
namespace verbb\formie\models;

use verbb\formie\helpers\VariableNode;
use verbb\formie\prosemirror\Renderer;

use craft\base\Model;
use craft\helpers\Json;

use yii\behaviors\AttributeTypecastBehavior;

class Notification extends Model
{
    // Public Properties
    // =========================================================================

    public $id;
    public $formId;
    public $templateId;
    public $name;
    public $enabled;
    public $subject;
    public $to;
    public $cc;
    public $bcc;
    public $replyTo;
    public $replyToName;
    public $from;
    public $fromName;
    public $content;
    public $attachFiles;
    public $uid;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        if (!$this->templateId) {
            $this->templateId = null;
        }
    }

    /**
     * @inheritDoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['typecast'] = [
            'class' => AttributeTypecastBehavior::class,
            'attributeTypes' => [
                'id' => AttributeTypecastBehavior::TYPE_INTEGER,
                'formId' => AttributeTypecastBehavior::TYPE_INTEGER,
                'templateId' => AttributeTypecastBehavior::TYPE_INTEGER,
                'name' => AttributeTypecastBehavior::TYPE_STRING,
                'enabled' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                'subject' => AttributeTypecastBehavior::TYPE_STRING,
                'to' => AttributeTypecastBehavior::TYPE_STRING,
                'cc' => AttributeTypecastBehavior::TYPE_STRING,
                'bcc' => AttributeTypecastBehavior::TYPE_STRING,
                'replyTo' => AttributeTypecastBehavior::TYPE_STRING,
                'replyToName' => AttributeTypecastBehavior::TYPE_STRING,
                'from' => AttributeTypecastBehavior::TYPE_STRING,
                'fromName' => AttributeTypecastBehavior::TYPE_STRING,
                'attachFiles' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                'uid' => AttributeTypecastBehavior::TYPE_STRING,
            ]
        ];

        return $behaviors;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->name;
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name', 'subject', 'to'], 'required'];
        $rules[] = [['name', 'subject', 'to', 'cc', 'bcc', 'replyTo', 'replyToName', 'from', 'fromName'], 'string'];
        $rules[] = [['formId', 'templateId'], 'number', 'integerOnly' => true];

        return $rules;
    }

    /**
     * Parses the JSON content and renders it as HTML.
     *
     * @return string
     */
    public function getParsedContent()
    {
        $content = Json::decode($this->content);

        $renderer = new Renderer();
        $renderer->addNode(VariableNode::class);

        return $renderer->render([
            'type' => 'doc',
            'content' => $content,
        ]);
    }
}