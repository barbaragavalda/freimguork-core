<?php

namespace Core\Routing;

class Project
{

    private ?string $url               = null;
    private string  $regularExpression = '';
    private int     $langPosition      = 0;

    private string $app       = '';
    private array  $folders   = array();
    private array  $languages = array();

    public function isEmpty(): bool
    {
        return $this->url === null;
    }

    public function getURL(): string
    {
        return $this->url;
    }

    public function getRegularExpression(): string
    {
        return $this->regularExpression;
    }

    public function getLangPosition(): int
    {
        return $this->langPosition;
    }

    public function getApp(): string
    {
        return $this->app;
    }

    public function getFolders(): array
    {
        return $this->folders;
    }

    public function getLanguages(): array
    {
        return $this->languages;
    }

    public function setURL(string $url): void
    {
        $this->url = $url;
    }

    public function setRegularExpression(string $regularExpression): void
    {
        $this->regularExpression = $regularExpression;
    }

    public function setLangPosition(int $position): void
    {
        $this->langPosition = $position;
    }

    public function setInfo(array $info): void
    {
        $this->app       = $info['app'];
        $this->folders   = $info['folders'];
        $this->languages = $info['languages'];
    }

}