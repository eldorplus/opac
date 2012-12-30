<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2005 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at                              |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Andrew Nagy <asnagy@webitecture.org>                        |
// |          Olivier Guilyardi <olivier@samalyse.com>                    |
// |          Mark Wiesemann <wiesemann@php.net>                          |
// +----------------------------------------------------------------------+
//
// $Id: DataGrid.php,v 1.1 2006/09/04 07:51:18 lon.briones Exp $

/*$Id: DataGrid.php,v 1.1 2006/09/04 07:51:18 lon.briones Exp $*/
require_once 'PEAR.php';

require_once 'Structures/DataGrid/Column.php';

// Rendering Drivers
define('DATAGRID_RENDER_TABLE',    'HTMLTable');
define('DATAGRID_RENDER_SMARTY',   'Smarty');
define('DATAGRID_RENDER_XML',      'XML');
define('DATAGRID_RENDER_XLS',      'XLS');
define('DATAGRID_RENDER_XUL',      'XUL');
define('DATAGRID_RENDER_CSV',      'CSV');
define('DATAGRID_RENDER_CONSOLE',  'Console');
define('DATAGRID_RENDER_PAGER',    'Pager');
define('DATAGRID_RENDER_SORTFORM', 'HTMLSortForm');

define('DATAGRID_RENDER_DEFAULT',  DATAGRID_RENDER_TABLE);

// DataSource Drivers
define('DATAGRID_SOURCE_ARRAY',     'Array');
define('DATAGRID_SOURCE_DATAOBJECT','DataObject');
define('DATAGRID_SOURCE_DB',        'DB');
define('DATAGRID_SOURCE_XML',       'XML');
define('DATAGRID_SOURCE_RSS',       'RSS');
define('DATAGRID_SOURCE_CSV',       'CSV');
define('DATAGRID_SOURCE_DBQUERY',   'DBQuery');
define('DATAGRID_SOURCE_DBTABLE',   'DBTable');
define('DATAGRID_SOURCE_MDB2',      'MDB2');

// PEAR_Error code for unsupported features
define('DATAGRID_ERROR_UNSUPPORTED', 1);

/**
 * Structures_DataGrid Class
 *
 * A PHP class to implement the functionality provided by the .NET Framework's
 * DataGrid control.  This class can produce a data driven list in many formats
 * based on a defined record set.  Commonly, this is used for outputting an HTML
 * table based on a record set from a database or an XML document.  It allows
 * for the output to be published in many ways, including an HTML table,
 * an HTML Template, an Excel spreadsheet, an XML document.  The data can
 * be sorted and paged, each cell can have custom output, and the table can be
 * custom designed with alternating color rows.
 *
 * Quick Example:
 * <code>
 * <?php
 * require 'Structures/DataGrid.php';
 * $datagrid =& new Structures_DataGrid();
 * $options = array('dsn' => 'mysql://user:password@host/db_name');
 * $datagrid->bind("SELECT * FROM my_table", $options);
 * $datagrid->render();
 * ?>
 * </code>
 *
 * @version  $Revision: 1.1 $
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid 
{
    /**
     * Renderer driver
     * @var object Structures_DataGrid_Renderer_* family
     * @access private
     */ 
    var $_renderer;

    /**
     * Renderer driver type
     * @var int DATAGRID_RENDER_* constant family
     * @access private
     */ 
    var $_rendererType = null;

    /**
     * Renderer driver backup
     * @var object Structures_DataGrid_Renderer_* family
     * @access private
     */ 
    var $_rendererBackup;

    /**
     * Renderer driver type backup
     * @var int DATAGRID_RENDER_* constant family
     * @access private
     */ 
    var $_rendererTypeBackup = null;

    /**
     * Whether the backup is an empty renderer
     * 
     * This property is set to true when _saveRenderer() is called and there
     * is no renderer loaded.
     * 
     * @var bool
     * @access private
     */ 
    var $_rendererEmptyBackup = false;

    /**
     * Array of columns.  Columns are defined as a DataGridColumn object.
     * @var array
     * @access private
     */
    var $columnSet = array();

    /**
     * Array of records.  
     * @var array
     * @access private
     */
    var $recordSet = array();

    /**
     * The Data Source Driver object
     * @var object Structures_DataGrid_DataSource
     * @access private
     */
    var $_dataSource;    
    
    /**
     * Fields/directions to sort the data by
     *
     * @var array Structure: array(fieldName => direction, ....)
     * @access private
     */
    var $sortSpec = array();

    /**
     * Default fields/directions to sort the data by
     *
     * @var array Structure: array(fieldName => direction, ....)
     * @access private
     */
    var $defaultSortSpec = array();

    /**
     * Limit of records to show per page.
     * @var string
     * @access private
     */
    var $rowLimit;

    /**
     * The current page to show.
     * @var string
     * @access private
     */
    var $page;

    /**
     * Whether the page number was provided at instantiation or not
     * @var bool
     * @access private
     */
    var $_forcePage; 
    
    /**
     * GET/POST/Cookie parameters prefix
     * @var string
     * @access private
     */
    var $_requestPrefix = '';    

    /**
     * Possible renderer types and their equivalent renderer constants
     * @var array
     * @access private
     */
    var $_rendererTypes = array(
        'html_table' => DATAGRID_RENDER_TABLE,
        'smarty' => DATAGRID_RENDER_SMARTY,
        'spreadsheet_excel_writer_workbook' => DATAGRID_RENDER_XLS,
        'console_table' => DATAGRID_RENDER_CONSOLE,
        'pager_common' => DATAGRID_RENDER_PAGER,
        'html_quickform' => DATAGRID_RENDER_SORTFORM
    );

    /**
     * Constructor
     *
     * Builds the DataGrid class.  The Core functionality and Renderer are
     * seperated for maintainability and to keep cohesion high.
     *
     * @param  string   $limit      The row limit per page.
     * @param  int      $page       The current page viewed.
     *                              Note: if you specify this, the "page" GET 
     *                              variable will be ignored.
     * @param  string   $rendererType   The type of renderer to use.
     * @return void
     * @access public
     */
    function Structures_DataGrid($limit = null, $page = null,
                                 $rendererType = null)
    {
        // Set the defined rowlimit
        $this->rowLimit = $limit;
        
        //Use set page number, otherwise automatically detect the page number
        if (!is_null($page)) {
            $this->page = $page;
            $this->_forcePage = true;
        } else {
            $this->page = 1;
            $this->_forcePage = false;
        }

        // Automatic handling of GET/POST/COOKIE variables
        $this->_parseHttpRequest();

        if (!is_null($rendererType)) {
            $this->setRenderer($rendererType);
        }
    }

    /**
     * Method used for debuging purposes only.  Displays a dump of the DataGrid
     * object.
     *
     * @access  public
     * @return  void
     */
    function dump()
    {
        echo '<pre>';
        print_r($this);
        echo '</pre>';
    }

    /**
     * Checks if a file exists in the include path
     *
     * @access private
     * @param  string   filename
     * @return boolean true success and false on error
     */
    function fileExists($file)
    {
        $fp = @fopen($file, 'r', true);
        if (is_resource($fp)) {
            @fclose($fp);
            return true;
         }
         return false;
    }

    /**
     * Checks if a class exists without triggering __autoload
     *
     * @param  string  className
     * @return bool true success and false on error
     *
     * @access public
     */
    function classExists($className)
    {
        if (version_compare(phpversion(), "5.0", ">=")) {
            return class_exists($className, false);
        }
        return class_exists($className);
    }

    /**
     * Load a Renderer or DataSource driver
     * 
     * @param string $className Name of the driver class
     * @access private
     * @return object The driver object or a PEAR_Error
     * @static
     */
    function &loadDriver($className)
    {
        if (!Structures_DataGrid::classExists($className)) {
            $file_name = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
            if (!include_once($file_name)) {
                if (!Structures_DataGrid::fileExists($file_name)) {
                    $msg = "unable to find package '$className' file '$file_name'";
                } else {
                    $msg = "unable to load driver class '$className' from file '$file_name'";
                }
                $error = PEAR::raiseError($msg);
                return $error;
            }
        }

        $driver =& new $className();
        return $driver;
    }
    
    /**
     * Datasource driver Factory
     *
     * A clever method which loads and instantiate data source drivers.
     *
     * Can be called in various ways:
     *
     * Detect the source type and load the appropriate driver with default
     * options:
     * <code>
     * $driver =& Structures_DataGrid::datasourceFactory($source);
     * </code>
     *
     * Detect the source type and load the appropriate driver with custom
     * options:
     * <code>
     * $driver =& Structures_DataGrid::datasourceFactory($source, $options);
     * </code>
     *
     * Load a driver for an explicit type (faster, bypasses detection routine):
     * <code>
     * $driver =& Structures_DataGrid::datasourceFactory($source, $options, $type);
     * </code>
     *
     * @access  private
     * @param   mixed   $source     The data source respective to the driver
     * @param   array   $options    An associative array of the form:
     *                              array(optionName => optionValue, ...)
     * @param   string  $type       The data source type constant (of the form 
     *                              DATAGRID_DATASOURCE_*)  
     * @uses    Structures_DataGrid_DataSource::_detectSourceType()     
     * @return  mixed               Returns the source driver object or 
     *                              PEAR_Error on failure
     * @static
     */
    function &datasourceFactory($source, $options=array(), $type=null)
    {
        if (is_null($type) &&
            !($type = Structures_DataGrid::_detectSourceType($source,
                                                             $options))) {
            $error = PEAR::raiseError('Unable to determine the data source type. '.
                                      'You may want to explicitly specify it.');
            return $error;
        }

        $className = "Structures_DataGrid_DataSource_$type";

        if (PEAR::isError($driver =& $this->loadDriver($className))) {
            return $driver;
        }
        
        $result = $driver->bind($source, $options);
       
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $driver;
        }
    }

    /**
     * Renderer driver factory
     *
     * Load and instantiate a renderer driver.
     * 
     * @access  private
     * @param   mixed   $source     The rendering container respective to the driver
     * @param   array   $options    An associative array of the form:
     *                              array(optionName => optionValue, ...)
     * @param   string  $type       The renderer type constant (of the form 
     *                              DATAGRID_RENDER_*)  
     * @uses    Structures_DataGrid_DataSource::_detectRendererType()     
     * @return  mixed               Returns the renderer driver object or 
     *                              PEAR_Error on failure
     */
    function &rendererFactory($type, $options = array())
    {
        $className = "Structures_DataGrid_Renderer_$type";

        if (PEAR::isError($driver =& $this->loadDriver($className))) {
            return $driver;
        }        

        if ($options) {
            $driver->setOptions($options);
        }

        return $driver;
    }

    /**
     * Render the datagrid
     *
     * You can call this method several times with different renderers.
     * 
     * @param  int      $type   Renderer type (optional)
     * @access public
     * @return mixed    True or PEAR_Error
     */
    function render($type = null,$options = array())
    {
        if (!is_null($type)) {
            $this->_saveRenderer();
            
            $test = $this->setRenderer($type);
            if (PEAR::isError($test)) {
                $this->_restoreRenderer();
                return $test;
            }
        } else if (!isset($this->_renderer)) {
            $test = $this->setRenderer(DATAGRID_RENDER_DEFAULT);
            if (PEAR::isError($test)) {
                return $test;
            }
        }
        
        if ($options) {
            $this->_renderer->setOptions($options);
        }
        
        $this->_renderer->isBuilt() || $this->build();
        $test = $this->_renderer->render();

        if (PEAR::isError($test)) {
            if ($test->getCode() == DATAGRID_ERROR_UNSUPPORTED) {
                $type = is_null($this->_rendererType) 
                        ? get_class($this->_renderer)
                        : $this->_rendererType;
                $this->_restoreRenderer();
                return PEAR::raiseError("The $type driver does not support the ".
                                        "render() method. Try using fill().");
            } else {
                $this->_restoreRenderer();
                return $test;
            }
        }
        $this->_restoreRenderer();

        return true;
    }

    /**
     * Return the datagrid output
     *
     * @access public
     * @return mixed The datagrid output (Usually a string: HTML, CSV, etc...)
     *               or a PEAR_Error
     */
    function getOutput($type = null, $options = array())
    {
        if (!is_null($type)) {
            $this->_saveRenderer();
            
            $test = $this->setRenderer($type);
            if (PEAR::isError($test)) {
                $this->_restoreRenderer();
                return $test;
            }
        } else if (!isset($this->_renderer)) {
            $this->setRenderer(DATAGRID_RENDER_DEFAULT);
        }
        
        if ($options) {
            $this->_renderer->setOptions($options);
        }
        
        $this->_renderer->isBuilt() || $this->build();
        
        $output = $this->_renderer->getOutput();
        
        if (PEAR::isError($output) && $output->getCode() == DATAGRID_ERROR_UNSUPPORTED) {
            $type = is_null($this->_rendererType) 
                    ? get_class($this->_renderer)
                    : $this->_rendererType;
            $this->_restoreRenderer();
            return PEAR::raiseError("The $type driver does not support the ".
                                    "getOutput() method. Try using render().");
        }
        
        $this->_restoreRenderer();
        return $output;
    }

    /**
     * Get Renderer
     *
     * Retrieves the renderer object as a reference
     *
     * @access public
     */
    function &getRenderer()
    {
        isset($this->_renderer) or $this->setRenderer(DATAGRID_RENDER_DEFAULT);
        return $this->_renderer;
    }

    /**
     * Set Renderer
     *
     * Defines which renderer to be used by the DataGrid
     *
     * @param  string   $renderer       The defined renderer string
     * @param  array    $options        Rendering options
     * @return mixed  True or PEAR_Error
     * @access public
     */
    function setRenderer($type, $options = array())
    {
        $renderer =& $this->rendererFactory($type, $options);
        if (!PEAR::isError($renderer)) {
            $this->_rendererType = $type;
            return $this->attachRenderer($renderer);
        } else {
            return $renderer;
        }
    }

    /**
     * Backup the current renderer
     * 
     * @return void
     * @access private
     */
    function _saveRenderer()
    {
        if (isset($this->_renderer)) {
            // The following line is a workaround for PHP bug 32660
            // See: http://bugs.php.net/bug.php?id=32660
            // Another solution would be to remove __get which is used only for BC
            $this->_rendererBackup = 1; 

            $this->_rendererBackup =& $this->_renderer;
            $this->_rendererTypeBackup = $this->_rendererType;

            unset($this->_renderer);
            $this->_rendererType = null;
        } else {
            $this->_rendererEmptyBackup = true;
        }
    }
    
    /**
     * Restore a previously saved renderer
     * 
     * If the $_renderer property was not set when _saveRenderer() got 
     * previously called, _restoreRenderer() will unset it.
     * 
     * @return void
     * @access private
     */
    function _restoreRenderer()
    {
        if ($this->_rendererEmptyBackup) {
            unset($this->_renderer);
            $this->_rendererType = null;
        } elseif (isset($this->_rendererBackup)) {
            $this->_renderer =& $this->_rendererBackup;
            $this->_rendererType = $this->_rendererTypeBackup;
        } 
        
        unset($this->_rendererBackup);
        $this->_rendererTypeBackup = null;
        $this->_rendererEmptyBackup = false;
    }
  
    /**
     * Tell the renderer how the data is sorted
     *
     * This method takes the "multiSort" capabilities of the datasource
     * into account. The idea is to correctly inform the renderer : for
     * example, a GET request may contain multiple fields and directions
     * to sort by. But, if the datasource does not support "multiSort"
     * then the renderer should not be told that the data is sorted according
     * to multiple fields. 
     *
     * It also properly set the "multiSortCapable" renderer flag (second argument
     * to Renderer::setCurrentSorting()).
     * 
     * This method requires both a datasource and renderer to be loaded.
     * 
     * It should be called even if $sortSpec is empty.
     *
     * @return void
     * @access private
     */
    function _setRendererCurrentSorting()
    {
        if ($this->_dataSource->hasFeature('multiSort')) {
            $this->_renderer->setCurrentSorting($this->sortSpec, true);
        } else {
            reset($this->sortSpec);
            list($field, $direction) = each($this->sortSpec);
            $this->_renderer->setCurrentSorting(
                    array($field => $direction), false);
        }
    }
    
    /**
     * Attach a rendering driver
     * 
     * @param object $renderer Driver object, subclassing 
     *                         Structures_DataGrid_Renderer
     * @return mixed           Either true or a PEAR_Error object
     * @access public
     */
    function attachRenderer(&$renderer)
    {
        if (is_subclass_of($renderer, 'structures_datagrid_renderer')) {
            // The following line is a workaround for PHP bug 32660
            // See: http://bugs.php.net/bug.php?id=32660
            $this->_renderer = 1;
            
            $this->_renderer =& $renderer;
            if (isset($this->_dataSource)) {
                $this->_renderer->setData($this->columnSet, $this->recordSet);
                $this->_renderer->setLimit($this->page, $this->rowLimit, 
                                          $this->getRecordCount());
                $this->_setRendererCurrentSorting();
            }
            if ($this->_requestPrefix) {
                $this->_renderer->setRequestPrefix($this->_requestPrefix); 
            }

        } else {
            return PEAR::raiseError('Invalid renderer type, ' . 
                                    'must be a valid renderer driver class');
        }

        return true;
    }

    
    
    /**
     * Fill a rendering container with data
     * 
     * @param object $container A rendering container of any of the supported
     *                          types (example: an HTML_Table object, 
     *                          a Spreadsheet_Excel_Writer object, etc...)
     * @param array  $options   Options for the corresponding rendering driver
     * @param string $type      Explicit type in case the container type 
     *                          can't be detected
     * @return mixed            Either true or a PEAR_Error object 
     * @access public
     */
    function fill(&$container, $options = array(), $type = null)
    {
        if (is_null($type)) {
            $type = $this->_detectRendererType($container);
            if (is_null($type)) {
                return PEAR::raiseError('The rendering container type can not '.
                                        'be automatically detected. Please ' . 
                                        'specify its type explicitly.');
            }
        }

        /* Is a renderer driver already loaded and does it exactly match 
         * the driver class name that corresponds to $type ? */
        //FIXME: is this redundant with the $rendererType property ?
        if (!isset($this->_renderer) 
            or !is_a($this->_renderer, "Structures_DataGrid_Renderer_$type")) {
            /* No, then load the right driver */
            $this->_saveRenderer();
            if (PEAR::isError($test = $this->setRenderer($type, $options))) {
                $this->_restoreRenderer();
                return $test;
            }
        }

        $test = $this->_renderer->setContainer($container);
        if (PEAR::isError($test)) {
            if ($test->getCode() == DATAGRID_ERROR_UNSUPPORTED) {
                $this->_restoreRenderer();
                return PEAR::raiseError("The $type driver does not support the " . 
                                        "fill() method. Try using render().");
            } else {
                $this->_restoreRenderer();
                return $test;
            }
        }

        $this->_renderer->isBuilt() || $this->build();

        $this->_restoreRenderer();
        return true;
    }

    /**
     * Create Default Columns
     *
     * This method handles the instantiation of default column objects,
     * when some records have been fetched from the datasource but columns
     * have neither been generated, nor provided by the user.
     *
     * @access private
     * @return void
     */
    function _createDefaultColumns()
    {
        if ((empty($this->columnSet)) && (!empty($this->recordSet))) {
            $arrayKeys = array_keys($this->recordSet[0]);
            foreach ($arrayKeys as $key) {
                $column = new Structures_DataGrid_Column($key, $key, $key);
                $this->addColumn($column);
                unset($column);
            }
        }
    }

    /**
     * Retrieves the current page number when paging is implemented
     *
     * @return int       the current page number
     * @access public
     */
    function getCurrentPage()
    {
        return $this->page;
    }

    /**
     * Define the current page number.  This is used when paging is implemented
     *
     * @access public
     * @param  mixed     $page       The current page number (as string or int).
     */
    function setCurrentPage($page)
    {
        $this->page = $page;
    }

    /**
     * Returns the total number of pages
     * (returns 0 if there are no records, returns 1 if there is no row limit)
     *
     * @return int       the total number of pages
     * @access public
     */
    function getPageCount()
    {
        if (is_null($this->rowLimit) || $this->getRecordCount() == 0) {
            return 1;
        } else {
            return ceil($this->getRecordCount() / $this->rowLimit);
        }
    }

    /**
     * Returns the number of columns
     *
     * @return int       the number of columns
     * @access public
     */
    function getColumnCount()
    {
        return count($this->columnSet);
    }

    /**
     * Returns the total number of records
     *
     * @return int       the total number of records
     * @access public
     */
    function getRecordCount()
    {
        if (isset($this->_dataSource)) {
            return $this->_dataSource->count();
        } else {
            // If there is no datasource then there is no data. The old way
            // of putting an array into the recordSet property does not exist
            // anymore. Binding an array loads the Array datasource driver.
            return 0;
        }
    }    

    /**
     * Returns the number of the first record of the current page
     * (returns 0 if there are no records, returns 1 if there is no row limit)
     *
     * @return int       the number of the first record currently shown
     * @access public
     */
    function getCurrentRecordNumberStart()
    {
        if (is_null($this->page)) {
            return 1;
        } elseif ($this->getRecordCount() == 0) {
            return 0;
        } else {
            return ($this->page - 1) * $this->rowLimit + 1;
        }
    }

    /**
     * Returns the number of the last record of the current page
     *
     * @return int       the number of the last record currently shown
     * @access public
     */
    function getCurrentRecordNumberEnd()
    {
        if (is_null($this->rowLimit)) {
            return $this->getRecordCount();
        } else {
            return
                min($this->getCurrentRecordNumberStart() + $this->rowLimit - 1,
                    $this->getRecordCount());
        }
    }

    /**
     * If you need to change the request variables, you can define a prefix.
     * This is extra useful when using multiple datagrids.
     *
     * @access  public
     * @param   string $prefix      The prefix to use on request variables;
     */
    function setRequestPrefix($prefix)
    {
        $this->_requestPrefix = $prefix;
        $this->_parseHttpRequest();

        if (isset($this->_renderer)) {

            $this->_renderer->setRequestPrefix($prefix);

            /* We just called parseHttpRequest() using a new requestPrefix.
             * The page and sort request might have changed, so we need
             * to pass them again to the renderer */
            $this->_renderer->setLimit($this->page, $this->rowLimit, 
                                      $this->getRecordCount());
            if (isset($this->_dataSource)) {
                $this->_setRendererCurrentSorting();
            }
        }
    }

    /**
     * Add a column, with optional position 
     *
     * @access  public
     * @param   object  $column     The Structures_DataGrid_Column object 
     *                              (reference to) 
     * @param   string  $position   One of: "last", "first", "after" or "before"
     *                              (default: "last")
     * @param   string  $relativeTo The name (label) or field name of the 
     *                              relative column, if $position is "after" 
     *                              or "before"
     * @return  mixed               PEAR_Error on failure, void otherwise
     */
    function addColumn(&$column, $position = 'last', $relativeTo = null)
    {
        if (!is_a($column, 'structures_datagrid_column')) {
            return PEAR::raiseError(__FUNCTION__ . "(): not a valid ".
                                    " Structures_DataGrid_Column object");
        } else {
            switch ($position) {
                case 'first'  :
                    array_unshift($this->columnSet, '');
                    $this->columnSet[0] =& $column;
                    break;
                case 'last'   :    
                    $this->columnSet[] =& $column;
                    break;
                case 'after'  :
                case 'before' :
                    $this->_createDefaultColumns();
                    // Has a relative column been specified ?
                    if (is_null($relativeTo)) {
                        return PEAR::raiseError(
                                __FUNCTION__ . "(): a relative column must be".
                                "specified when using position \"$position\"");
                    }
                    // Yes, does it exist ?
                    foreach ($this->columnSet as $i => $relColumn) {
                        if ($relColumn->columnName == $relativeTo 
                                || $relColumn->fieldName == $relativeTo) {
                            $relIndex = $i;
                        }
                    }
                    // If it does not exist, return an error
                    if (!isset($relIndex)) {
                        return PEAR::raiseError(
                                __FUNCTION__ . "(): Can't find a relative ".
                                "column which name or field name is: ".
                                $relativeTo);
                    }
                    // It exists, add the column after or before it
                    if ($position == 'after') {
                        array_splice($this->columnSet, $relIndex + 1,  0, '');
                        $this->columnSet[$relIndex + 1] =& $column;
                    } else {
                        array_splice($this->columnSet, $relIndex,  0, '');
                        $this->columnSet[$relIndex] =& $column;
                    }
                    break;
            }
        } 
    }

    /**
     * Find a column by name (label) 
     *
     * @access  public
     * @param   string   $name      The name (label) of the column to look for
     * @return  object              Either the column object (reference to) or 
     *                              false if there is no such column
     */
    function &getColumnByName($name)
    {
        $this->_createDefaultColumns();
        foreach ($this->columnSet as $key => $column) {
            if ($column->columnName === $name) {
                return $this->columnSet[$key];
            }
        }
        $ret = false;
        return $ret;
    }

    /**
     * Find a column by field name
     *
     * @access  public
     * @param   string   $fieldName The field name of the column to look for
     * @return  object              Either the column object (reference to) or 
     *                              false if there is no such column
     */
    function &getColumnByField($fieldName)
    {
        $this->_createDefaultColumns();
        foreach ($this->columnSet as $key => $column) {
            if ($column->fieldName === $fieldName) {
                return $this->columnSet[$key];
            }
        }
        $ret = false;
        return $ret;
    }

    /**
     * A simple way to add a record set to the datagrid
     *
     * @access  public
     * @param   mixed   $container  The record set in any of the supported data
     *                              source types
     * @param   array   $options    Optional. The options to be used for the
     *                              data source
     * @param   string  $type       Optional. The data source type
     * @return  bool                True if successful, otherwise PEAR_Error.
     */
    function bind($container, $options = array(), $type = null)
    {
        $source =& Structures_DataGrid::datasourceFactory($container, $options, $type);
        if (!PEAR::isError($source)) {
            return $this->bindDataSource($source);
        } else {
            return $source;
        }
    }

    /**
     * Allows binding to a data source driver.
     *
     * @access  public
     * @param   mixed   $source     The data source driver object
     * @return  mixed               True if successful, otherwise PEAR_Error
     */
    function bindDataSource(&$source)
    {
        if (is_subclass_of($source, 'structures_datagrid_datasource')) {
            $this->_dataSource =& $source;
            if (PEAR::isError($result = $this->fetchDataSource())) {
                unset($this->_dataSource);
                return $result;
            }
            if ($columnSet = $this->_dataSource->getColumns()) {
                $this->columnSet = array_merge($this->columnSet, $columnSet);
            }
            if (isset($this->_renderer)) {
                $this->_renderer->setData($this->columnSet, $this->recordSet);
                $this->_renderer->setLimit($this->page, $this->rowLimit, 
                                          $this->getRecordCount());
                if ($this->sortSpec) {
                    $this->_setRendererCurrentSorting();
                }
            }
        } else {
            return PEAR::raiseError('Invalid data source type, ' . 
                                    'must be a valid data source driver class');
        }

        return true;
    }

    /**
     * Request the datasource to sort its data
     *
     * @return void
     * @access private
     */
    function _sortDataSource()
    {
        if (!empty($this->sortSpec)) {
            if ($this->_dataSource->hasFeature('multiSort')) {
                $this->_dataSource->sort($this->sortSpec);
            } else {
                reset ($this->sortSpec);
                list ($sortBy, $direction) = each ($this->sortSpec);
                $this->_dataSource->sort($sortBy, $direction);
            }
        }
    }
    
    /**
     * Fetch data from the datasource 
     *
     * @return mixed Either true or a PEAR_Error object
     * @access private
     */
    function fetchDataSource()
    {
        if (isset($this->_dataSource)) {
            // Determine Page
            $page = $this->page ? $this->page - 1 : 0;

            // Sort the data
            if (empty($this->sortSpec) and $this->defaultSortSpec) {
                $this->sortSpec = $this->defaultSortSpec;
            }
           
            $this->_sortDataSource();

            // Fetch the Data
            $recordSet = $this->_dataSource->fetch(
                            ($page * $this->rowLimit),
                            $this->rowLimit);

            if (PEAR::isError($recordSet)) {
                return $recordSet;
            } else {
                $this->recordSet = array_merge($this->recordSet, $recordSet);
                return true;
            }
        } else {
            return PEAR::raiseError("Cannot fetch data: no datasource driver loaded.");
        }
    }

    /**
     * Sorts the records by the defined field.
     *
     * Do not use this method if data is coming from a database as sorting
     * is much faster coming directly from the database itself.
     *
     * @access  public
     * @param   array   $sortSpec   Sorting specification
     *                              Structure: array(fieldName => direction, ...)
     * @param   string  $direction  Deprecated. Put the direction(s) into
     *                              $sortSpec
     * @return  void
     */
    function sortRecordSet($sortSpec, $direction = 'ASC')
    {
        if (is_array ($sortSpec)) {
            $this->sortSpec = $sortSpec;
        } else {
            $this->sortSpec = array($sortSpec => $direction);
        } 

        if (isset($this->_dataSource)) {
            $this->_sortDataSource();
        } 
    }

    /**
     * Set default sorting specification
     *
     * If there is no sorting query in the HTTP request, and if the 
     * sortRecordSet() method is not called, then the specification
     * passed to setDefaultSort() will be used.
     * 
     * This is especially useful if you want the data to already be
     * sorted when a user first see the datagrid.
     * 
     * @param array $sortSpec   Sorting specification
     *                          Structure: array(fieldName => direction, ...)
     * @return void
     * @access public
     */
    function setDefaultSort($sortSpec)
    {
        $this->defaultSortSpec = $sortSpec;
    }
   
    /**
     * Read an HTTP request argument
     *
     * This methods take the $_requestPrefix into account, and respect the 
     * POST, GET, COOKIE read order.
     *
     * @param   string  $name   Argument name
     * @return  mixed           Argument value or null
     * @access  private
     */
    function _getRequestArgument($name)
    {
        $value = null;
        $prefix = $this->_requestPrefix;
        if (isset($_REQUEST["$prefix$name"])) {
            if (isset($_POST["$prefix$name"])) {
                $value = $_POST["$prefix$name"];
            } elseif (isset($_GET["$prefix$name"])) {
                $value = $_GET["$prefix$name"];
            } elseif (isset($_COOKIE["$prefix$name"])) {
                $value = $_COOKIE["$prefix$name"];
            } 
        }
        return $value;
    }
    
    /**
     * Parse HTTP Request parameters
     * 
     * Determine page, sort and direction values
     * 
     * @access  private
     * @return  array      Associative array of parsed arguments, each of these 
     *                     defaulting to null if not found. 
     */
    function _parseHttpRequest()
    {
        if (!$this->_forcePage) {
            if (!($this->page = $this->_getRequestArgument ('page'))) {
                $this->page = 1;
            } 
            if (!is_numeric($this->page)) {
                $this->page = 1;
            }
        } 

        if ($orderBy = $this->_getRequestArgument('orderBy')) {
            if (is_array($orderBy)) {
                $direction = $this->_getRequestArgument('direction');
                $this->sortSpec = array();
                foreach ($orderBy as $index => $field) {
                    if (!empty($field)) {
                        $this->sortSpec[$field] = $direction[$index];
                    }
                } 
            } else {
                if (!($direction = $this->_getRequestArgument('direction'))) {
                    $direction = 'ASC';
                }
                $this->sortSpec = array ($orderBy => $direction);
            }
        }
    }     

    /**
     * Detect datasource container type
     *
     * @param   mixed   $source     Some kind of source
     * @param   array   $options    Options passed to dataSourceFactory()
     * @return  string              The type constant of this source or null if
     *                              it couldn't be detected
     * @access  private
     * @todo    Add CSV detector.  Possible rewrite in IFs to allow for
     *          hierarchy for seperating file handle sources from others
     */
    function _detectSourceType($source, $options = array())
    {
        switch(true) {
            // DB_DataObject
            case is_object($source) && is_subclass_of($source, 'db_dataobject'):
                return DATAGRID_SOURCE_DATAOBJECT;
                break;

            // DB_Result
            case strtolower(get_class($source)) == 'db_result':
                return DATAGRID_SOURCE_DB;
                break;
                
            // Array
            case is_array($source):
                return DATAGRID_SOURCE_ARRAY;
                break;

            // RSS
            case is_string($source) && stristr('<rss', $source):
            case is_string($source) && stristr('<rdf:RDF', $source):
            case is_string($source) && strpos($source, '.rss') !== false:
                return DATAGRID_SOURCE_RSS;
                break;

            // XML
            case is_string($source) && preg_match('#^ *<\?xml#', $source) === 1:
                return DATAGRID_SOURCE_XML;
                break;
            
            // DBQuery / MDB2
            case is_string($source) &&
                preg_match('#SELECT\s.*\sFROM#is', $source) === 1:
                if (array_key_exists('dbc', $options) &&
                    is_subclass_of($options['dbc'], 'db_common')) {
                    return DATAGRID_SOURCE_DBQUERY;
                }
                return DATAGRID_SOURCE_MDB2;
                break;

            // DB_Table
            case is_object($source) && is_subclass_of($source, 'db_table'):
                return DATAGRID_SOURCE_DBTABLE;
                break;

            // CSV
            //case is_string($source):
            //    return DATAGRID_SOURCE_CSV;
            //    break;
                
            default:
                return null;
                break;
        }
    }

    /**
     * Detect rendering container type
     * 
     * @param object $container The rendering container
     * @return string           The container type or null if unrecognized
     * @access private
     */
    function _detectRendererType(&$container)
    {
        foreach ($this->_rendererTypes as $class => $type) {
            if (is_a($container, $class)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Build the datagrid
     * 
     * @return mixed Either true or a PEAR_Error object
     * @access public
     */
    function build()
    {
        $this->_createDefaultColumns();
        if (isset($this->_dataSource)) {
            isset($this->_renderer) or $this->setRenderer(DATAGRID_RENDER_DEFAULT);
            $this->_renderer->build();
            return true;
        } else {
            return PEAR::raiseError("Cannot build the datagrid: no datasource driver loaded");
        }
    }

    /**
     * Provide some BC fix (requires PHP5)
     * 
     * This is a PHP5 magic method used to simulate the old public 
     * $renderer property
     */
    function __get($var)
    {
        if ($var == 'renderer') {
            isset($this->_renderer) or $this->setRenderer(DATAGRID_RENDER_DEFAULT);
            return $this->_renderer;
        }
    }

    /**
     * Set a single renderer option
     *
     * @param   string  $name       Option name
     * @param   mixed   $value      Option value
     * @access  public
     */
    function setRendererOption($name,$value)
    {
        $this->setRendererOptions(array($name => $value));
    }

    /**
     * Set multiple renderer options
     *
     * @param   array   $options    An associative array of the form:
     *                              array("option_name" => "option_value",...)
     * @access  public
     */
    function setRendererOptions($options)
    {
        isset($this->_renderer) or $this->setRenderer(DATAGRID_RENDER_DEFAULT);
        $this->_renderer->setOptions($options);
    }

    /**
     * Set a single datasource option
     *
     * @param   string  $name       Option name
     * @param   mixed   $value      Option value
     * @access  public
     */
    function setDataSourceOption($name,$value)
    {
        return $this->setDataSourceOptions(array($name => $value));
    }

    /**
     * Set multiple datasource options
     *
     * @param   array   $options    An associative array of the form:
     *                              array("option_name" => "option_value",...)
     * @access  public
     */
    function setDataSourceOptions($options)
    {
        if (isset($this->_dataSource)) {
            $this->_dataSource->setOptions($options);
        } else {
            return PEAR::raiseError('Unable to set options; no datasource loaded.');
        }
    }

}

?>
