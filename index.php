<?php
class APIClient
{
    public function getURL(string $url)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $output = curl_exec($curl);

        curl_close($curl);

        return $output;
    }
}

class Travel
{
    public function getTravelList()
    {
        $apiClient = new APIClient();
        $response = $apiClient->getURL('https://5f27781bf5d27e001612e057.mockapi.io/webprovise/travels');     
        return json_decode($response, true);
    }
}
class Company
{
    public function getCompanyList()
    {
        $apiClient = new APIClient();
        $response = $apiClient->getURL('https://5f27781bf5d27e001612e057.mockapi.io/webprovise/companies');     
        return json_decode($response, true);
    }

    public function getCompanyWithChildren($travels)
    {
        $companies = $this->getCompanyList();
        $this->appendCost($companies, $travels);

        $parentCompanies = array_filter($companies, function($company) {
            return empty($company['parent_id']);
        });
        $this->loadChildren($parentCompanies, $companies);
        return $parentCompanies;
    }

    private function appendCost(array &$companies, array $travels)
    {
        foreach($companies as &$company) {
            $travelCosts = array_filter($travels, function($travel) use ($company) {
                return $travel['companyId'] == $company['id'];
            });
            $travelCosts = array_map(function($travelCost) {
                return $travelCost['price'];
            }, $travelCosts);
            $company['cost'] = array_sum($travelCosts);
        }
    }

    private function loadChildren(&$parentCompanies, $companies)
    {
        foreach($parentCompanies as &$parentCompany) {
            $children = array_filter($companies, function($company) use ($parentCompany) {
                return $company['parentId'] == $parentCompany['id'];
            });
            $parentCompany['children'] = array_values($children);
            $this->loadChildren($parentCompany['children'], $companies);
            //calculate children cost
            $childrenCosts = array_map(function($child) {
                return $child['cost'];
            }, $parentCompany['children']);
            $parentCompany['cost'] = $parentCompany['cost'] + array_sum($childrenCosts);
        }
    }

}
class TestScript
{
    public function execute()
    {
        $start = microtime(true);
        // Enter your code here
        $travelClass = new Travel();
        $travels = $travelClass->getTravelList();
        $companyClass = new Company();
        $companies = $companyClass->getCompanyWithChildren($travels);
        echo json_encode($companies);
        echo "\r\n";

        echo 'Total time: '.  (microtime(true) - $start);
    }
}
(new TestScript())->execute();
