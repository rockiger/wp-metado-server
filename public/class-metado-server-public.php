<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://rockiger.com
 * @since      1.0.0
 *
 * @package    Metado_Server
 * @subpackage Metado_Server/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Metado_Server
 * @subpackage Metado_Server/public
 * @author     Marco Laspe <marco@rockiger.com>
 */
class Metado_Server_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function setup_new_user(int $user_id) {
		add_user_meta($user_id, 'metado_active_board', 'main-board'); //! should later be a foreign key
	}

	public function example_extend_wpgraphql_schema() {

		register_graphql_object_type('Board', [
			'description' => __('A kanban like board', 'metado-server'),
			'fields' => [
				'id' => [
					'type' => ['non_null' => 'Int'],
				],
				'columns' => [
					'type' => ['list_of' => 'Column'],
					'description' => __('The columns shown in the board', 'metado-server'),
				],
				'isDeleted' => [
					'type' => ['non_null' => 'Boolean']
				],
				'projects' => [
					'type' => ['list_of' => 'Project'], //!
					'description' => __('The projects that should be shown on this board', 'metado-server')
				],
				'showBacklog' => [
					'type' => ['non_null' => 'Boolean']
				],
				'title' => [
					'type' => ['non_null' => 'String']
				],
				'createdBy' => [
					'type' => ['non_null' => 'User']
				],
				'updatedBy' => [
					'type' => ['non_null' => 'User']
				],
				'createdAt' => [
					'type' => ['non_null' => 'Datetime']
				],
				'updatedAt' => [
					'type' => ['non_null' => 'Datetime']
				],
				'tasks' => [
					'type' => ['list_of' => 'Task']
				]
			],
		]);

		register_graphql_object_type('Column', [
			'description' => __('A column in a kanban board', 'metado-server'),
			'fields' => [
				'id' => [
					'type' => ['non_null' => 'Int'],
				],
				'taskIds' => ['type' => ['list_of' => 'String']],
				'title' => ['type' => ['non_null' => 'String']],
				'noOfTasksToShow' => ['type' => 'Int']
			],
		]);

		register_graphql_object_type('Project', [
			'description' => __('A metado project', 'metado-server'),
			'fields' => [
				'id' => [
					'type' => ['non_null' => 'Int'],
				],
				'title' => ['type' => ['non_null' => 'String']],
				'type' => ['type' => ['non_null' => 'String']],
				'createdAt' => ['type' => ['non_null' => 'String']],
				'meta' => ['type' => 'ProjectMeta']
			],
		]);


		register_graphql_object_type('ProjectMeta', [
			'description' => __('Metainformation to a metado project', 'metado-server'),
			'fields' => [
				'fullname' => ['type' => 'String']
			],
		]);

		register_graphql_object_type('Task', [
			'description' => __('A metado project', 'metado-server'),
			'fields' => [
				'id' => [
					'type' => ['non_null' => 'Int'],
				],
				'description' => ['type' => 'String'],
				'title' => ['type' => ['non_null' => 'String']],
				'createdAt' => ['type' => ['non_null' => 'String']],
				'finishedAt' => ['type' => 'String'],
				'projectId' => ['type' => ['non_null' => 'Int']]
			],
		]);

		register_graphql_field('RootQuery', 'Board', [
			'args' => [
				'id' => [
					'type' => ['non_null' => 'Int'],
					'description' => __( 'The ID of the board', 'metado-server')
				]
				],
			'type' => 'Board',
			'description' => 'Desrcibe what the field sohuld be used for',
			'resolve' => function ($source, $args, $context, $info ) {
				global $wpdb;
				$id = $args['id'];

				$board_results = $wpdb->get_row("SELECT * FROM wp_metado_boards WHERE id = $id LIMIT 1;"); // check if current user is owner of board
				$projects_results = $wpdb->get_results("SELECT * FROM `wp_metado_projects` p WHERE p.id IN (SELECT project_id FROM wp_metado_boards_2_projects WHERE board_id = $id);");
				$tasks_results = $wpdb->get_results("SELECT * from wp_metado_tasks t WHERE t.project_id IN (SELECT p.id FROM `wp_metado_projects` p WHERE p.id IN (SELECT project_id FROM wp_metado_boards_2_projects WHERE board_id = $id));");

				if ($board_results) {
					$columns =  json_decode($board_results->columns);
					return [
						'id' => $board_results->id,
						'columns' => $columns,
						'isDeleted' => $board_results->isDeleted,
						'projects' => array_map(fn ($el) => [
							'id' => $el->id, 'title' => $el->title, 'type' => $el->project_type, 'createdAt' => $el->createdAt,
							'meta' => json_decode($el->project_meta)
						], $projects_results), //!
						'showBacklog' => $board_results->showBacklog,
						'title' => $board_results->title,
						'createdBy' => wp_get_current_user(),
						'updatedBy' => wp_get_current_user(),
						'createdAt' => $board_results->createdAt,
						'updatedAt' => $board_results->updatedAt,
						'tasks' => array_map(fn ($el) => [
							'id' => $el->id, 'title' => $el->title, 'description' => $el->task_description, 'finishedAt' => $el->finishedAt, 'projectId' => $el->project_id
						], $tasks_results)
					];
				} else {
					return [
						'id' => 0,
						'columns' => [],
						'isDeleted' => false,
						'projects' => [],
						'showBacklog' => true,
						'title' => '',
						'createdBy' => null,
						'updatedBy' => null,
						'createdAt' => '',
						'updatedAt' => '',
						'tasks' => []
					];
				}
			}
		]);

		register_graphql_mutation('updateBoard', [

			# inputFields expects an array of Fields to be used for inputting values to the mutation
			'inputFields'         => [
				'id' => [
					'type' => 'Int',
					'description' => __('The id of the board', 'metado-server'),
				],
				'showBacklog' => [
					'type' => 'Boolean',
					'description' => __('The state of the backlog', 'metado-server'),
				],
			],

			# outputFields expects an array of fields that can be asked for in response to the mutation
			# the resolve function is optional, but can be useful if the mutateAndPayload doesn't return an array
			# with the same key(s) as the outputFields
			'outputFields'        => [
				'exampleOutput' => [
					'type' => 'String',
					'description' => __('Description of the output field', 'your-textdomain'),
					'resolve' => function ($payload, $args, $context, $info) {
						return isset($payload['exampleOutput']) ? $payload['exampleOutput'] : null;
					}
				]
			],

			# mutateAndGetPayload expects a function, and the function gets passed the $input, $context, and $info
			# the function should return enough info for the outputFields to resolve with
			'mutateAndGetPayload' => function ($input, $context, $info) {
				global $wpdb;
				$id = $input['id'];
				$showBacklog = $input['showBacklog'];
				$wpdb->update($wpdb->prefix . 'metado_boards', ['showBacklog' => $showBacklog], ['id' => $id]);
				// Do any logic here to sanitize the input, check user capabilities, etc
				$exampleOutput = null;
				if (!empty($input['exampleInput'])) {
					$exampleOutput = 'Your input was: ' . $input['exampleInput'];
				}
				return [
					'exampleOutput' => $exampleOutput,
				];
			}
		]);
	}
}
