<?php

namespace App\Models\Ads;

use App\Models\TableHeader;

class JobAd extends AdBase
{
    protected ?string $industry = null;
    protected ?string $workSchedule = null;
    protected ?string $experience = null;
    protected ?string $salary = null;

    public function __construct(array $row, TableHeader $propertyColumns)
    {
        parent::__construct($row, $propertyColumns);

        $this->industry = isset($row[$propertyColumns->industry])
            ? htmlspecialchars($row[$propertyColumns->industry])
            : null;
        $this->workSchedule = isset($row[$propertyColumns->workSchedule])
            ? htmlspecialchars($row[$propertyColumns->workSchedule])
            : null;
        $this->experience = isset($row[$propertyColumns->experience])
            ? htmlspecialchars($row[$propertyColumns->experience])
            : null;
        $this->salary = isset($row[$propertyColumns->salary])
            ? htmlspecialchars($row[$propertyColumns->salary])
            : null;
    }

    public function toAvitoXml() : string
    {
        $defaultTags = $this->generateDefaultXML();

        $resultXml = $this->addTagIfPropertySet($this->industry, "Industry");
        $resultXml.= $this->addTagIfPropertySet($this->workSchedule, "JobType");
        $resultXml.= $this->addTagIfPropertySet($this->experience, "Experience");
        $resultXml.= $this->addTagIfPropertySet($this->salary, "Salary");

        return <<<AVITOXML
    <Ad>
$defaultTags
$resultXml
    </Ad>
AVITOXML;
    }
}
