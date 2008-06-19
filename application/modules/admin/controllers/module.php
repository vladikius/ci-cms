<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
	
	class Module extends Controller {
		
		function Module()
		{

			parent::Controller();
			
			$this->load->library('administration');
			
			$this->template['module'] = "admin";
		}
		
		function index()
		{
			
			$this->db->order_by('ordering');
			$query = $this->db->get('modules');
			
			
			if($rows = $query->result_array())
			{
				$modules = array();
				foreach ( $rows as $module ) 
				{
					$modules[ $module['name'] ] = $module;
				}
			}
			/*
			Check if all physical modules are installed
			*/
			unset( $module );
			$handle = opendir(APPPATH.'modules');

			if ($handle)
			{
				while ( false !== ($module = readdir($handle)) )
				{
					// make sure we don't map silly dirs like .svn, or . or ..

					if ( (substr($module, 0, 1) != ".") && ($module != 'admin') )
					{
						if ( !isset($modules[$module]))
						{
							$modules[$module] = array(
								'name' => $module,
								'status' => -1,
								'description' => null,
								'version' => null
							);
						}
					}
				}
			}
			
			$this->template['modules'] = $modules;
			$this->layout->load($this->template, 'module/index');

		}
		
		function activate($module = null)
		{
			if (is_null($module))
			{
				$this->session->set_flashdata('notification', __("Please select a module"));
				redirect('admin/module');
			}
			$data = array('status' => 1);
			$this->db->where(array('name'=> $module, 'ordering >=' => 100));
			$this->db->update('modules', $data);
			$this->session->set_flashdata('notification', __("The module is activated"));
			redirect('admin/module');
		}

		function deactivate($module = null)
		{
			if (is_null($module))
			{
				$this->session->set_flashdata('notification', __("Please select a module"));
				redirect('admin/module');
			}
			$data = array('status' => 0);
			$this->db->where(array('name'=> $module, 'ordering >=' => 100));
			$this->db->update('modules', $data);
			$this->session->set_flashdata('notification', __("The module is deactivated"));
			redirect('admin/module');
		}


		function uninstall($module = null)
		{
			if (is_null($module))
			{
				$this->session->set_flashdata('notification', __("Please select a module"));
				redirect('admin/module');
			}
			
			$this->db->where(array('name'=> $module, 'ordering >=' => 100));
			$this->db->delete('modules');
			$this->session->set_flashdata('notification', __("The module is uninstalled"));
			redirect('admin/module');
		}
		
		function install($module = null)
		{
			if (is_null($module)) 
			{
				$this->session->set_flashdata('notification', __("Please select a module"));
				redirect('admin/module');
			}
			
			if ($this->_is_installed($module))
			{
				$this->session->set_flashdata('notification', __("The module you are trying to install is already installed"));
				redirect('admin/module');
			}
			
			//now install it
			if (is_readable(APPPATH.'modules/'.$module.'/setup.xml'))
			{
				$this->load->helper('xml');
				$xmldata = join('', file(APPPATH.'modules/'.$module.'/setup.xml'));
				$xmlarray = xmlize($xmldata);
				if (isset($xmlarray['module']['@']['name']) && $xmlarray['module']['@']['name'] == $module)
				{
					$data['name'] = trim($xmlarray['module']['@']['name']);
					$data['description'] = isset($xmlarray['module']['#']['description'][0]['#']) ? trim($xmlarray['module']['#']['description'][0]['#']): '';
					$data['version'] = isset($xmlarray['module']['@']['version']) ? trim($xmlarray['module']['@']['version']) : '';
					$data['status'] = 0;
					$data['ordering'] = 100;
					if (file_exists(APPPATH.'modules/'.$module.'/controllers/admin.php'))
					{
						$data['with_admin'] = 1;
					}
					//execute queries
					if (isset($xmlarray['module']['#']['install'][0]['#']['query']))
					{
						$queries = $xmlarray['module']['#']['install'][0]['#']['query'];
						foreach ($queries as $query)
						{
							$this->db->query( $query['#'] );
						}
					} 
					$this->session->set_flashdata('notification', __("The module is installed. Now you need to activate it."));
					$this->db->insert('modules', $data);
					redirect('admin/module');
				}
				else
				{
					$this->session->set_flashdata('notification', __("The module setup file is not valid."));
					redirect('admin/module');
				}
			
			}
			else
			{
				$this->session->set_flashdata('notification', __("Setup file not readable."));
				redirect('admin/module');
			}
		}
		
		function _is_installed($module)
		{
			$query = $this->db->get_where('modules', array('name' => $module), 1);
			if ($query->num_rows() > 0)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		

	}

?>