<?php
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/cextrest.php');

function logMessage($message, $file = 'SPAManager_Class_log.txt') {
    // Set the default timezone
    date_default_timezone_set('Asia/Kuala_Lumpur');

    // Format the message with a timestamp
    $formattedMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;

    // Write the message to the specified log file
    file_put_contents($file, $formattedMessage, FILE_APPEND);
}


class BitrixSPAStageManager {
    private $settings;
    private $entityTypeId;
    private $pipelineId;
    private $entityId;
    private $statusIdTemplate;
    private $stageColors = [
        '#2FC6F6', '#55D0E0', '#47E4C2', '#FFA900', '#9DCF00',
        '#FF5752', '#468EE5', '#1EAE43', '#B38B22', '#3AC8C4'
    ];

    public function __construct($entityTypeId) {
        $this->entityTypeId = $entityTypeId;
        $this->initializePipeline();
    }

    private function getRandomColor() {
        return $this->stageColors[array_rand($this->stageColors)];
    }


    private function initializePipeline() {
        try {
            // Step 2: Get pipeline ID with corrected response structure
            $result = CRestExt::call('crm.category.list', [
                'entityTypeId' => $this->entityTypeId
            ]);

            if (!isset($result['result']['categories']) || empty($result['result']['categories'])) {
                throw new Exception("No categories found for entityTypeId: {$this->entityTypeId}");
            }

            // Get the default pipeline or the first one
            $pipeline = null;
            foreach ($result['result']['categories'] as $category) {
                if ($category['isDefault'] === 'Y') {
                    $pipeline = $category;
                    break;
                }
            }

            // If no default pipeline found, take the first one
            if (!$pipeline) {
                $pipeline = $result['result']['categories'][0];
            }

            if (!isset($pipeline['id'])) {
                throw new Exception("Failed to get pipeline ID from categories");
            }

            $this->pipelineId = $pipeline['id'];

            // Step 3: Construct ENTITY_ID and STATUS_ID templates
            $this->entityId = "DYNAMIC_{$this->entityTypeId}_STAGE_{$this->pipelineId}";
            $this->statusIdTemplate = "DT{$this->entityTypeId}_{$this->pipelineId}:";

            return true;
        } catch (Exception $e) {
            throw new Exception("Pipeline initialization failed: " . $e->getMessage());
        }
    }

    public function cleanupDefaultStages() {
        try {
            error_log("Starting cleanup of default stages for ENTITY_ID: " . $this->entityId);
            
            // Step 4: Get current stages and delete unnecessary ones
            $result = CRestExt::call('crm.status.list', [
                'filter' => ['ENTITY_ID' => $this->entityId]
            ]);
    
            error_log("Current stages found: " . print_r($result['result'], true));
    
            if (isset($result['result']) && is_array($result['result'])) {
                $stagesToDelete = [];
                $stagesToKeep = [];
                
                foreach ($result['result'] as $status) {
                    if (!in_array($status['NAME'], ['Success', 'Failed'])) {
                        $stagesToDelete[] = [
                            'ID' => $status['ID'],
                            'NAME' => $status['NAME'],
                            'STATUS_ID' => $status['STATUS_ID']
                        ];
                    } else {
                        $stagesToKeep[] = [
                            'ID' => $status['ID'],
                            'NAME' => $status['NAME'],
                            'STATUS_ID' => $status['STATUS_ID']
                        ];
                    }
                }
    
                error_log("Stages to be deleted: " . print_r($stagesToDelete, true));
                error_log("Stages to be kept: " . print_r($stagesToKeep, true));
    
                foreach ($stagesToDelete as $stage) {
                    error_log("Attempting to delete stage: " . print_r($stage, true));
                    
                    $deleteResult = CRestExt::call('crm.status.delete', [
                        'id' => $stage['ID'],
                        'params'=>[
                             'FORCED' => 'Y'
                        ]
                       
                    ]);
                    
                    error_log("Delete result for stage {$stage['NAME']} (ID: {$stage['ID']}): " . 
                        print_r($deleteResult, true));
                }
    
                // Verify deletion by getting the list again
                $verificationResult = CRestExt::call('crm.status.list', [
                    'filter' => ['ENTITY_ID' => $this->entityId]
                ]);
                
                error_log("Remaining stages after deletion: " . print_r($verificationResult['result'], true));
            }
        } catch (Exception $e) {
            error_log("Stage cleanup failed with error: " . $e->getMessage());
            throw new Exception("Stage cleanup failed: " . $e->getMessage());
        }
    }

    public function createCustomStages() {
        $stages = [
            ['name' => 'Initialized', 'sort' => 5],
            ['name' => 'Reviewee Pending', 'sort' => 10],
            ['name' => 'Reviewer Pending', 'sort' => 15],
            ['name' => 'Partner Pending', 'sort' => 20],
            ['name' => 'Submitted', 'sort' => 25]
        ];

        $createdStages = [];

        try {
            foreach ($stages as $stage) {
                $statusId = $this->statusIdTemplate . $this->sanitizeStageId($stage['name']);
                
                $stageData = [
                    'fields' => [
                        'ENTITY_ID' => $this->entityId,
                        'STATUS_ID' => $statusId,
                        'NAME' => $stage['name'],
                        'SORT' => $stage['sort'],
                        'COLOR' => $this->getRandomColor(),
                        'SYSTEM' => 'N',
                        'SEMANTICS' => 'P'  // Processing semantics for in-progress stages
                    ]
                ];

                // Debug log
                error_log("Creating stage with data: " . print_r($stageData, true));

                $result = CRestExt::call('crm.status.add', $stageData);

                if (isset($result['result'])) {
                    $createdStages[] = [
                        'id' => $result['result'],
                        'name' => $stage['name'],
                        'entityId' => $this->entityId,
                        'statusId' => $statusId
                    ];
                } else {
                    error_log("Failed to create stage: " . print_r($result, true));
                    throw new Exception("Failed to create stage: " . $stage['name']);
                }
            }

            return $createdStages;
        } catch (Exception $e) {
            throw new Exception("Stage creation failed: " . $e->getMessage());
        }
    }

    private function sanitizeStageId($name) {
        // Convert stage name to a simple uppercase identifier
        $sanitized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name));
        return $sanitized;
    }

    public function getStageSetupData() {
        return [
            'entityTypeId' => $this->entityTypeId,
            'pipelineId' => $this->pipelineId,
            'entityId' => $this->entityId,
            'statusIdTemplate' => $this->statusIdTemplate
        ];
    }
}


class BitrixSPAManager {
    private $settings;
    private $instanceId;

    private $lastSpaId = 0;
    private $lastFieldCounter = 0;
    private $lastRecordCounter = 0;

    public function __construct($instanceId) {
        $this->instanceId = $this->sanitizeInstanceId($instanceId);
        $this->loadSettings();
    }

    private function sanitizeInstanceId($instanceId) {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $instanceId);
    }

    private function getSettingsPath() {
        // Changed to use spa_settings.json
        return INSTANCE_SETTINGS_DIR . '/' . $this->instanceId . '/spa_settings.json';
    }


    public function getEntityTypeId($spaId){
      return $this->settings['spas'][$spaId]['entityTypeId'];
    }

    private function loadSettings() {
        $settingsPath = $this->getSettingsPath();
        if (file_exists($settingsPath)) {
            $settingsContent = file_get_contents($settingsPath);
            if ($settingsContent !== false) {
                $this->settings = json_decode($settingsContent, true);
                $this->lastSpaId = $this->settings['lastSpaId'] ?? 0;
                $this->lastFieldCounter = $this->settings['lastFieldCounter'] ?? 0;
                $this->lastRecordCounter = $this->settings['lastRecordCounter'] ?? 0;
            } else {
                $this->initializeSettings();
            }
        } else {
            $this->initializeSettings();
        }
    }

    private function initializeSettings() {
        $this->settings = [
            'instanceId' => $this->instanceId,
            'installDate' => date('Y-m-d H:i:s'),
            'spas' => [],
            'lastSpaId' => 0,
            'lastFieldCounter' => 0,
            'lastRecordCounter' => 0,
            'status' => 'active'
        ];
        $this->saveSettings();
    }

    private function saveSettings() {
        $settingsPath = $this->getSettingsPath();
        $directory = dirname($settingsPath);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $jsonData = json_encode($this->settings, JSON_PRETTY_PRINT);
        if ($jsonData === false) {
            throw new Exception("Failed to encode settings data");
        }
        
        $result = file_put_contents($settingsPath, $jsonData, LOCK_EX);
        if ($result === false) {
            throw new Exception("Failed to save settings file");
        }
    }

    public function createSPA($domain, $rest_member_id) {
        $this->lastSpaId++;
        $spaTitle = "Performance Appraisal SPA";
         // Get authentication data
         
         if (!$rest_member_id || !$domain) {
             throw new Exception("Missing required Bitrix24 parameters");
         }
         

        $result = CRestExt::call(
            'crm.type.add',
            [
                'fields' => [
                    'title' => $spaTitle,
                    'isAutomationEnabled' => "Y",
                    'isBeginCloseDatesEnabled' => "Y",
                    'isBizProcEnabled' => "Y",
                    'isCategoriesEnabled' => "Y",
                    'isClientEnabled' => "Y",
                    'isDocumentsEnabled' => "Y",
                    'isLinkWithProductsEnabled' => "Y",
                    'isMycompanyEnabled' => "Y",
                    'isObserversEnabled' => "Y",
                    'isRecyclebinEnabled' => "Y",
                    'isSetOpenPermissions' => "Y",
                    'isSourceEnabled' => "Y",
                    'isStagesEnabled' => "Y",
                    'isUseInUserfieldEnabled' => "Y",
                    'linkedUserFields' => [
                        "CALENDAR_EVENT|UF_CRM_CAL_EVENT" => "true",
                        "TASKS_TASK|UF_CRM_TASK" => "true",
                    ]
                ],
            ]
        );

     
        if (isset($result['result']['type']['id']) && isset($result['result']['type']['entityTypeId'])) {
            $spaId = $result['result']['type']['id'];
            $entityTypeId = $result['result']['type']['entityTypeId'];

            $this->settings['spas'][$spaId] = [
                'rest_member_id' => $rest_member_id,  // Add rest_member_id
                'domain' => $domain,
                'entityTypeId' => $entityTypeId,
                'fields' => [],
                'records' => []
            ];
            
            $this->saveSettings();
            
            return [
                'success' => true,
                'result' => [
                    'id' => $spaId,
                    'entityTypeId' => $entityTypeId,
                    'rest_member_id' => $rest_member_id,
                    'domain' => $domain
                ]
            ];
        } else {
            throw new Exception("Failed to create SPA: Invalid response structure");
        }
    }

    //Stage Part
    public function setupSPAStages($spaId) {
        if (!isset($this->settings['spas'][$spaId])) {
            throw new Exception("SPA not found");
        }

        $entityTypeId = $this->settings['spas'][$spaId]['entityTypeId'];
        $stageManager = new BitrixSPAStageManager($entityTypeId);

        // Clean up default stages
        $stageManager->cleanupDefaultStages();

        // Create new stages
        $createdStages = $stageManager->createCustomStages();

        // Save stage information
        $this->settings['spas'][$spaId]['pipeline'] = [
            'setup' => $stageManager->getStageSetupData(),
            'stages' => $createdStages
        ];

        $this->saveSettings();

        return $createdStages;
    }

    public function createCustomField($spaId, $fieldName) {
        if (!isset($this->settings['spas'][$spaId])) {
            throw new Exception("SPA ID {$spaId} not found in settings");
        }

        $this->lastFieldCounter++;
        $formattedFieldName = trim(strtoupper($fieldName));
        $fullFieldName = "UF_CRM_{$spaId}_{$formattedFieldName}";

        
        $userTypeId = 'string';  // default value
        switch ($formattedFieldName) {
            case 'REVIEWEE':
            case 'REVIEWER':
            case 'PARTNER':
                $userTypeId = 'employee';
                break;
        }

        $result = CRestExt::call(
            'userfieldconfig.add',
            [
                'moduleId' => "crm",
                'field' => [
                    'entityId' => "CRM_{$spaId}",
                    'fieldName' => $fullFieldName,
                    'userTypeId' => $userTypeId,
                    'editFormLabel' => [
                        'en' => "{$formattedFieldName}"
                    ]
                ],
            ]
        );
     
        if (isset($result['result']['field']['fieldName'])) {
            $fieldData = [
                'name' => $formattedFieldName,
                'fieldName' => $result['result']['field']['fieldName'],
                'id' => $result['result']['field']['id'] ?? null
            ];
            
            $this->settings['spas'][$spaId]['fields'][$fieldName] = $fieldData;
            $this->saveSettings();
            
            return [
                'success' => true,
                'result' => [
                    'fieldName' => $fullFieldName,
                    'displayName' => "Custom Field {$formattedFieldName}",
                    'data' => $fieldData
                ]
            ];
        } else {
            logMessage($result);
            throw new Exception("Failed to create custom field");
        }
    }

   
    

    public function createSPARecord($spaId, $customFields = []) {
		if (!isset($this->settings['spas'][$spaId])) {
            logMessage("SPA ID {$spaId} not found in settings");
			throw new Exception("SPA ID {$spaId} not found in settings");
		}
	
		$this->lastRecordCounter++;
		$recordTitle = "Employee Feedback Record " . date('Y');
	
        logMessage("Generated record title: $recordTitle");
        // Get stored fields from json
        $storedFields = $this->settings['spas'][$spaId]['fields'];

        $fields = [];
        foreach ($customFields as $baseName => $value) {
            // Direct lookup using the base field name as key
            if (isset($storedFields[$baseName])) {
                // Get the full field name from stored fields
                $fullFieldName = $storedFields[$baseName]['fieldName'];
                // Convert UF_CRM to ufCrm for API call
                $apiFieldName = 'ufCrm' . substr($fullFieldName, 6);
                $fields[$apiFieldName] = $value;
            }
        }

        $key = 'ufCrm_' . $spaId . '_REVIEWEE';

        // Check if the key exists in the array before accessing it
        if (isset($fields[$key])) {
            $revieweetoresp = $fields[$key];
            logMessage("Reviewee value: " . $revieweetoresp);
        } else {
            logMessage("Key $key does not exist in the fields array.");
        }

        logMessage(json_encode($fields));

		$result = CRestExt::call(
			'crm.item.add',
			[
				'entityTypeId' => $this->settings['spas'][$spaId]['entityTypeId'],
				'fields' => array_merge(
                    $fields,
                    ['title' => $recordTitle],
                    ['assignedById'=> $revieweetoresp],
                    ['stageId' => 'Reviewee Pending']
                )
			]
		);

        logMessage("CRM API response: " . json_encode($result));
	
		if (isset($result['result']['item']['id'])) {
			$recordId = $result['result']['item']['id'];
            logMessage("Record created successfully with ID: $recordId");

			$this->settings['spas'][$spaId]['records'][$recordId] = [
				'title' => $recordTitle,
				'createdAt' => date('Y-m-d H:i:s'),
				'lastUpdated' => date('Y-m-d H:i:s')
			];
			
			$this->saveSettings();
			  logMessage("Settings saved successfully");
			return [
				'success' => true,
				'result' => [
					'id' => $recordId,
					'title' => $recordTitle
				]
			];
		} else {
            logMessage("Failed to create SPA record");
			throw new Exception("Failed to create SPA record");
		}
	}

    public function updateSPARecord($spaId, $recordId, $userType = null, $customFields = []) {
        if (!isset($this->settings['spas'][$spaId])) {
            throw new Exception("SPA ID not found in settings");
        }
    
        // Get stored fields
        $storedFields = $this->settings['spas'][$spaId]['fields'];
    
        // Get pipeline stages
        $stages = $this->settings['spas'][$spaId]['pipeline']['stages'];
        
        // Function to find stage status ID by name
        $findStageStatusId = function($stageName) use ($stages) {
            foreach ($stages as $stage) {
                if (strtoupper($stage['name']) === strtoupper($stageName)) {
                    return $stage['statusId'];
                }
            }
            return null;
        };
    
        // Determine the status ID based on userType
        $statusId = '';
        switch(strtolower($userType)) {
            case 'reviewee':
                $statusId = $findStageStatusId('Reviewer Pending');
                break;
            case 'reviewer':
                $statusId = $findStageStatusId('Partner Pending');
                break;
            case 'partner':
                $statusId = $findStageStatusId('Submitted');
                break;
            case 'forminit':
                $statusId = $findStageStatusId('Reviewee Pending');
                break;
            default:
                $statusId = $findStageStatusId('Initialized');
                break;
        }
    
        // If no valid status ID was found, use the first stage as fallback
        if (!$statusId && !empty($stages)) {
            $statusId = $stages[0]['statusId'];
            error_log("Warning: No matching stage found for userType '$userType'. Using default stage.");
        }
    
        $fields = [];
        foreach ($customFields as $baseName => $value) {
            // Direct lookup using the base field name as key
            if (isset($storedFields[$baseName])) {
                // Get the full field name from stored fields
                $fullFieldName = $storedFields[$baseName]['fieldName'];
                // Convert UF_CRM to ufCrm for API call
                $apiFieldName = 'ufCrm' . substr($fullFieldName, 6);
                $fields[$apiFieldName] = $value;
            }
        }
    
        // Debug output
        error_log("Updating record with fields: " . print_r($fields, true));
        error_log("Setting status ID to: " . $statusId);
    
        return CRestExt::call(
            'crm.item.update',
            [
                'entityTypeId' => $this->settings['spas'][$spaId]['entityTypeId'],
                'id' => $recordId,
                'fields' => array_merge(
                    $fields,
                    ['stageId' => $statusId]
                )
            ]
        );
    }
}
?>