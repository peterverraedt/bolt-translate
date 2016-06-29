<?php
namespace Bolt\Extension\Verraedt\Translate\Storage\Repository;

use Bolt\Storage\Repository;

class FieldTranslationRepository extends Repository
{
    public function queryExistingFields($id, $contenttype, $field)
    {
        $query = $this->createQueryBuilder()
            ->select('locale, id', 'fieldname')
            ->where('content_id = :id')
            ->andWhere('contenttype = :contenttype')
            ->andWhere('fieldname = :name')
            ->setParameters([
                'id'          => $id,
                'contenttype' => $contenttype,
                'name'        => $field,
            ]);

        return $query;
    }

    public function getExistingFields($id, $contenttype, $field)
    {
        $query = $this->queryExistingFields($id, $contenttype, $field);
        $results = $query->execute()->fetchAll();

        $fields = [];

        if (!$results) {
            return $fields;
        }

        foreach ($results as $result) {
            $fields[$result['locale']] = $result['id'];
        }

        return $fields;
    }
}
