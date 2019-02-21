<?php

namespace Wabel\Zoho\CRM\BeanComponents;


class Field
{

    /**
     * @var boolean
     */
    private $req;

    /**
     * @var string
     */
    private $type;

    /**
     * @var boolean
     */
    private $isreadonly;

    /**
     * @var int
     */
    private $maxlength;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string|null
     */
    private $dv;

    /**
     * @var boolean
     */
    private $customfield;


    /**
     * @var string[]
     */
    private $values;

    /**
     * @var string
     */
    private $phpType;

    /**
     * @var string
     */
    private $getter;

    /**
     * @var string
     */
    private $setter;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $apiName;

    /**
     * @var boolean
     */
    private $system;

    /**
     * @var string|null
     */
    private $lookupModuleName;

    /**
     * @return bool
     */
    public function getReq(): bool
    {
        return $this->req;
    }

    /**
     * @param bool $req
     */
    public function setReq(bool $req): void
    {
        $this->req = $req;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return bool
     */
    public function getIsreadonly(): bool
    {
        return $this->isreadonly;
    }

    /**
     * @param bool $isreadonly
     */
    public function setIsreadonly(bool $isreadonly): void
    {
        $this->isreadonly = $isreadonly;
    }

    /**
     * @return int
     */
    public function getMaxlength(): int
    {
        return $this->maxlength;
    }

    /**
     * @param int $maxlength
     */
    public function setMaxlength(int $maxlength): void
    {
        $this->maxlength = $maxlength;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * @return string|null
     */
    public function getDv(): string
    {
        return $this->dv;
    }

    /**
     * @param string|null $dv
     */
    public function setDv($dv): void
    {
        $this->dv = $dv;
    }

    /**
     * @return bool
     */
    public function getCustomfield(): bool
    {
        return $this->customfield;
    }

    /**
     * @param bool $customfield
     */
    public function setCustomfield(bool $customfield): void
    {
        $this->customfield = $customfield;
    }

    /**
     * @return string[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param string[] $values
     */
    public function setValues(array $values): void
    {
        $this->values = $values;
    }

    /**
     * @return string
     */
    public function getPhpType(): string
    {
        return $this->phpType;
    }

    /**
     * @param string $phpType
     */
    public function setPhpType(string $phpType): void
    {
        $this->phpType = $phpType;
    }

    /**
     * @return string
     */
    public function getGetter(): string
    {
        return $this->getter;
    }

    /**
     * @param string $getter
     */
    public function setGetter(string $getter): void
    {
        $this->getter = $getter;
    }

    /**
     * @return string
     */
    public function getSetter(): string
    {
        return $this->setter;
    }

    /**
     * @param string $setter
     */
    public function setSetter(string $setter): void
    {
        $this->setter = $setter;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getApiName(): string
    {
        return $this->apiName;
    }

    /**
     * @param string $apiName
     */
    public function setApiName(string $apiName): void
    {
        $this->apiName = $apiName;
    }

    /**
     * @return bool
     */
    public function isSystem(): bool
    {
        return $this->system;
    }

    /**
     * @param bool $system
     */
    public function setSystem(bool $system): void
    {
        $this->system = $system;
    }

    /**
     * @return null|string
     */
    public function getLookupModuleName(): ?string
    {
        return $this->lookupModuleName;
    }

    /**
     * @param null|string $lookupModuleName
     */
    public function setLookupModuleName(?string $lookupModuleName): void
    {
        $this->lookupModuleName = $lookupModuleName;
    }




}