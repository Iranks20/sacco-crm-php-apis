<?php

namespace TheCodeMill\FlashMessage;

class Message
{
    /**
     * INFO message status.
     *
     * @const STATUS_INFO
     */
    const STATUS_INFO = 'INFO';

    /**
     * SUCCESS message status.
     *
     * @const STATUS_SUCCESS
     */
    const STATUS_SUCCESS = 'SUCCESS';

    /**
     * WARNING message status.
     *
     * @const STATUS_WARNING
     */
    const STATUS_WARNING = 'WARNING';

    /**
     * DANGER message status.
     *
     * @const STATUS_DANGER
     */
    const STATUS_DANGER = 'DANGER';

    /**
     * Message content.
     *
     * @var string
     */
    protected $content;

    /**
     * Message status.
     *
     * @var string
     */
    protected $status;

    /**
     * Construct a new Message instance.
     *
     * @param string $content
     * @param string $status
     * @return void
     * @throws \TheCodeMill\FlashMessage\InvalidStatusException
     */
    public function __construct($content, $status)
    {
        if (!in_array($status, static::statuses())) {
            throw new InvalidStatusException('Invalid message status');
        }

        $this->content = $content;
        $this->status = $status;
    }

    /**
     * Return all supported message statuses.
     *
     * @return array
     */
    public static function statuses()
    {
        return [
            static::STATUS_INFO,
            static::STATUS_SUCCESS,
            static::STATUS_WARNING,
            static::STATUS_DANGER,
        ];
    }

    /**
     * Return the message content.
     *
     * @return string
     */
    public function content()
    {
        return $this->content;
    }

    /**
     * Return the message status.
     *
     * @return string
     */
    public function status()
    {
        return $this->status;
    }

    /**
     * Return whether the message has an INFO status.
     *
     * @return bool
     */
    public function isInfo()
    {
        return $this->status() == static::STATUS_INFO;
    }

    /**
     * Return whether the message has a SUCCESS status.
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->status() == static::STATUS_SUCCESS;
    }

    /**
     * Return whether the message has a WARNING status.
     *
     * @return bool
     */
    public function isWarning()
    {
        return $this->status() == static::STATUS_WARNING;
    }

    /**
     * Return whether the message has a DANGER status.
     *
     * @return bool
     */
    public function isDanger()
    {
        return $this->status() == static::STATUS_DANGER;
    }

    /**
     * Helper method to generate an INFO message.
     *
     * @param string $content
     * @return \TheCodeMill\FlashMessage\Message
     */
    public static function info($content)
    {
        return new static($content, static::STATUS_INFO);
    }

    /**
     * Helper method to generate a SUCCESS message.
     *
     * @param string $content
     * @return \TheCodeMill\FlashMessage\Message
     */
    public static function success($content)
    {
        return new static($content, static::STATUS_SUCCESS);
    }

    /**
     * Helper method to generate a WARNING message.
     *
     * @param string $content
     * @return \TheCodeMill\FlashMessage\Message
     */
    public static function warning($content)
    {
        return new static($content, static::STATUS_WARNING);
    }

    /**
     * Helper method to generate an DANGER message.
     *
     * @param string $content
     * @return \TheCodeMill\FlashMessage\Message
     */
    public static function danger($content)
    {
        return new static($content, static::STATUS_DANGER);
    }
}
