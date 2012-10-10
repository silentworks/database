<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EloquentHasOneTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testCreateMethodProperlyCreatesNewModel()
	{
		$relation = $this->getRelation();
		$created = $this->getMock('Illuminate\Database\Eloquent\Model', array('save', 'getKey'));
		$created->expects($this->once())->method('save')->will($this->returnValue(true));
		$created->expects($this->once())->method('getKey')->will($this->returnValue(1));
		$relation->getRelated()->shouldReceive('newInstance')->once()->with(array('name' => 'taylor', 'foreign_key' => 1))->andReturn($created);

		$this->assertEquals(1, $relation->create(array('name' => 'taylor')));
	}


	public function testRelationIsProperlyInitialized()
	{
		$relation = $this->getRelation();
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('setRelation')->once()->with('foo', null);
		$models = $relation->initRelation(array($model), 'foo');

		$this->assertEquals(array($model), $models);
	}


	public function testEagerConstraintsAreProperlyAdded()
	{
		$relation = $this->getRelation();
		$relation->getQuery()->shouldReceive('whereIn')->once()->with('foreign_key', array(1, 2));
		$model1 = new EloquentHasOneModelStub;
		$model1->id = 1;
		$model2 = new EloquentHasOneModelStub;
		$model2->id = 2;
		$relation->addEagerConstraints(array($model1, $model2));
	}


	public function testModelsAreProperlyMatchedToParents()
	{
		$relation = $this->getRelation();

		$result1 = new EloquentHasOneModelStub;
		$result1->foreign_key = 1;
		$result2 = new EloquentHasOneModelStub;
		$result2->foreign_key = 2;

		$model1 = new EloquentHasOneModelStub;
		$model1->id = 1;
		$model2 = new EloquentHasOneModelStub;
		$model2->id = 2;
		$model3 = new EloquentHasOneModelStub;
		$model3->id = 3;

		$models = $relation->match(array($model1, $model2, $model3), new Collection(array($result1, $result2)), 'foo');

		$this->assertEquals(1, $models[0]->foo->foreign_key);
		$this->assertEquals(2, $models[1]->foo->foreign_key);
		$this->assertNull($models[2]->foo);
	}


	protected function getRelation()
	{
		$builder = m::mock('Illuminate\Database\Eloquent\Builder');
		$builder->shouldReceive('where')->with('foreign_key', '=', 1);
		$related = m::mock('Illuminate\Database\Eloquent\Model');
		$builder->shouldReceive('getModel')->andReturn($related);
		$parent = m::mock('Illuminate\Database\Eloquent\Model');
		$parent->shouldReceive('getKey')->andReturn(1);
		return new HasOne($builder, $parent, 'foreign_key');
	}

}

class EloquentHasOneModelStub extends Illuminate\Database\Eloquent\Model {
	public $foreign_key = 'foreign.value';
}