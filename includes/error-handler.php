<?php
/**
 * SMARTBIOFIT - Enhanced Error Handler
 */

class SmartBioFitErrorHandler {
    private static $logFile = 'logs/smartbiofit-errors.log';
    
    public static function logError($message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message";
        
        if (!empty($context)) {
            $logEntry .= ' Context: ' . json_encode($context);
        }
        
        $logEntry .= PHP_EOL;
        
        error_log($logEntry, 3, self::$logFile);
    }
    
    public static function handleException($exception) {
        self::logError('Exception: ' . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
    
    public static function handleError($errno, $errstr, $errfile, $errline) {
        self::logError('Error: ' . $errstr, [
            'type' => $errno,
            'file' => $errfile,
            'line' => $errline
        ]);
    }
}

// Set error handlers
set_exception_handler(['SmartBioFitErrorHandler', 'handleException']);
set_error_handler(['SmartBioFitErrorHandler', 'handleError']);
?>