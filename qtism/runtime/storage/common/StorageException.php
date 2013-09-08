<?php

namespace qtism\runtime\storage\common;

use \Exception;

/**
 * The StorageException class represents exceptions that AssessmentTestSession
 * Storage Services encounter an error.
 * 
 * @author Jérôme Bogaerts <jerome@taotesting.com>
 *
 */
class StorageException extends Exception {
    
    /**
     * The error code to be used when the nature of the error
     * is unknown. Should be used in absolute necessity. Otherwise,
     * use the appropriate error code.
     * 
     * @var integer
     */
    const UNKNOWN = 0;
    
    /**
     * Error code to be used when an error occurs while
     * instantiating an AssessmentTestSession.
     * 
     * @var integer
     */
    const INSTANTIATION = 1;
    
    /**
     * Error code to use when an error occurs while
     * persisting an AssessmentTestSession.
     * 
     * @var integer
     */
    const PERSITANCE = 2;
    
    /**
     * Error code to use when an error occurs while
     * retrieving an AssessmentTestSession.
     * 
     * @var integer
     */
    const RETRIEVAL = 3;
    
    /**
     * Create a new StorageException instance.
     * 
     * @param string $message A human-readable message describing the encountered error.
     * @param integer $code A code enabling client-code to identify the cause of the error.
     * @param Exception $previous An optional previous Exception that was thrown and catched.
     */
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
    
}