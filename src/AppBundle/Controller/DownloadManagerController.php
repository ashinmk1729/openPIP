<?php
namespace AppBundle\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Identifier;
use AppBundle\Form\IdentifierType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use AppBundle\Entity\Protein;
use AppBundle\Entity\Interaction;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Form\ChoiceList\ArrayChoiceList;
/**
 * Search controller.
 *
 * @Route("/admin/download")
 */
class DownloadManagerController extends Controller
{

    /**
     * Search Home
     *
     * @Route("/multi_fasta/{search_term}", name="multi_fasta", options={"expose": true}))
     * @Method({"GET", "POST"})
     */
    public function multi_fastaAction($search_term)
    {
    
        $em = $this->getDoctrine()->getManager();
    
    
    
    
        $identifier_repository = $this->getDoctrine()
        ->getRepository('AppBundle:Identifier');
        	
        $identifier = $identifier_repository->findOneByIdentifier($search_term);
        $identifier_identifier = $identifier->getIdentifier();
        $identifier_naming_convention = $identifier->getNamingConvention();
        $protein = $identifier->getProtein();
        $protein_gene_name = $protein->getGeneName();
        $protein_id = $protein->getId();
        $protein_name = $protein->getName();
        $protein_sequence = $protein->getSequence();
        $protein_description = $protein->getDescription();
    
    
        $query = $em->createQuery(
                        "SELECT i
				FROM AppBundle:Interaction i
				WHERE i.interactor_A = :interactor_A"
                        );
    
        $query->setParameter('interactor_A', $protein_id);
        	
        $interaction_result_array = $query->getResult();
    
    
        $interactor_B_fasta = '';
    
    
    
        foreach($interaction_result_array as $interaction_result){
    
            $interaction_id = $interaction_result->getId();
            $interactor_A = $interaction_result->getInteractorA();
            $interactor_B = $interaction_result->getInteractorB();
            $binding_start = $interaction_result->getBindingStart();
            $binding_end = $interaction_result->getBindingEnd();
            $score = $interaction_result->getScore();
            $domain_id = $interaction_result->getDomain();
            	
            	
            $query = $em->createQuery(
                            "SELECT p
				FROM AppBundle:Protein p
				WHERE p.id = :interactor_B"
                            );
            	
            $query->setParameter('interactor_B', $interactor_B);
            	
            $interactor_B_array = $query->getResult();
            	
            $_interactor_B = $interactor_B_array[0];
            	
            $interactor_B_gene_name = $_interactor_B->getGeneName();
            	
            $interactor_B_name = $_interactor_B->getName();
    
            $interactor_B_sequence = $_interactor_B->getSequence();
            	
            $interactor_B_id  = $_interactor_B->getId();
            	
            $interactor_B_fasta .= '>' . $interactor_B_gene_name . "\r\n" . $interactor_B_sequence . "\r\n";
        }
    
        $response = new Response();
    
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $identifier_identifier . '_multi_fasta.fa');
    
        $response->setContent($interactor_B_fasta);
    
        return $response;
    
    }
    
    /**
     * Search Home
     *
     * @Route("/csv/{search_term}", name="csv", options={"expose": true}))
     * @Method({"GET", "POST"})
     */
    public function csvAction($search_term)
    {
    
        $psi_mitab = "";
        $response = new Response();
    
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . 'csv.txt');
    
        $response->setContent($psi_mitab);
    
        return $response;
    }
    
    
    /**
     * Search Home
     *
     * @Route("/psi_mitab/{search_term}", name="psi_mitab", options={"expose": true}))
     * @Method({"GET", "POST"})
     */
    public function psi_mitabAction($search_term)
    {
        $search_query = $search_term;
    
        $request = $this->getRequest();
        $search_setting_organism = $request->query->get('organism');
        $search_setting_domain = $request->query->get('domain');
        $search_setting_score = $request->query->get('score');
        $search_setting_max_interactions = $request->query->get('max_interactions');
         
        //$domain = getDomainByType($search_setting_domain);
         
        $identifier_repository = $this->getDoctrine()
        ->getRepository('AppBundle:Identifier');
    
        $identifier = $identifier_repository->findOneByIdentifier($search_query);
        $identifier_identifier = $identifier->getIdentifier();
        $identifier_naming_convention = $identifier->getNamingConvention();
        $protein = $identifier->getProtein();
         
        $protein_id = $protein->getId();
        $protein_name = $protein->getName();
        $protein_sequence = $protein->getSequence();
        $protein_description = $protein->getDescription();
         
        $repository = $this->getDoctrine()->getManager();
        //->getRepository('AppBundle:Interaction');
         
        $query_builder = $repository->createQueryBuilder('i');
        $query_builder->select('i');
        $query_builder->from('AppBundle:Interaction', 'i');
        $query_builder->where('i.interactor_A = :interactor_A');
        $query_builder->setParameter('interactor_A', $protein_id);
        if($search_setting_score){
            $query_builder->andWhere('i.score >= :score');
            $query_builder->setParameter('score', $search_setting_score);
             
        }
         
        if($search_setting_max_interactions){
            $query_builder->setMaxResults($search_setting_max_interactions);
        }
         
        $query = $query_builder->getQuery();
         
         
        $psi_mitab = "#ID(s) interactor A\tID(s) interactor B\tAlt. ID(s) interactor A\tAlt. ID(s)interactor B\tAlias(es) interactor A\tAlias(es) interactor B\tInteraction detection method(s)\tPublication 1st author(s)\tPublication Identifier(s)\tTaxid interactor A\tTaxid interactor B\tInteraction type(s)\tSource database(s)\tInteraction identifier(s)\tConfidence value(s)\tExpansion method(s)\tBiological role(s)interactor A\tBiological role(s) interactor B\tExperimental role(s) interactor A\tExperimental role(s) interactor B\tType(s) interactor A\tType(s) interactor B\tXref(s) interactor A\tXref(s) interactor B\tInteraction Xref(s)\tAnnotation(s) interactor A\tAnnotation(s) interactor B\tInteraction annotation(s)\tHost organism(s)\tInteraction parameter(s)\tCreation date\tUpdate date\tChecksum(s) interactor A\tChecksum(s) interactor B\tInteraction Checksum(s)	Negative\tFeature(s) interactor A\tFeature(s) interactor B\tStoichiometry(s) interactor A\tStoichiometry(s) interactor B\tIdentification method participant A\tIdentification method participant B\r\n";
    
         
        $interaction_result_array = $query->getResult();
         
        foreach($interaction_result_array as $interaction_result){
            $em = $this->getDoctrine()->getManager();
            $interaction_id = $interaction_result->getId();
            $interactor_A = $interaction_result->getInteractorA();
            $interactor_B = $interaction_result->getInteractorB();
            $binding_start = $interaction_result->getBindingStart();
            $binding_end = $interaction_result->getBindingEnd();
            $score = $interaction_result->getScore();
            $domain_id = $interaction_result->getDomain();
    
    
            $query = $em->createQuery(
                            "SELECT p
				FROM AppBundle:Protein p
				WHERE p.id = :interactor_B"
                            );
    
            $query->setParameter('interactor_B', $interactor_B);
    
            $interactor_B_array = $query->getResult();
    
            $_interactor_B = $interactor_B_array[0];
    
            $interactor_B_gene_name = $_interactor_B->getGeneName();
    
            $interactor_B_name = $_interactor_B->getName();
             
            $interactor_B_sequence = $_interactor_B->getSequence();
    
            $interactor_B_id  = $_interactor_B->getId();
             
            $query = $em->createQuery(
                            "SELECT p
				FROM AppBundle:Protein p
				WHERE p.id = :interactor_A"
                            );
             
            $query->setParameter('interactor_A', $interactor_A);
             
            $interactor_A_array = $query->getResult();
             
            $_interactor_A = $interactor_A_array[0];
             
            $interactor_A_gene_name = $_interactor_A->getGeneName();
             
            $interactor_A_name = $_interactor_A->getName();
    
            $interactor_A_sequence = $_interactor_A->getSequence();
             
            $interactor_A_id  = $_interactor_A->getId();
             
            $identifier_repository = $this->getDoctrine()
            ->getRepository('AppBundle:Identifier');
             
            $identifier_A = $identifier_repository->findOneByIdentifier($interactor_A_name);
            $identifier_A_identifier = $identifier_A->getIdentifier();
            $identifier_A_naming_convention = $identifier_A->getNamingConvention();
            
            $identifier_B = $identifier_repository->findOneByIdentifier($interactor_B_name);
            $identifier_B_identifier = $identifier_B->getIdentifier();
            $identifier_B_naming_convention = $identifier_B->getNamingConvention();
            
            $psi_mitab .= "$identifier_A_naming_convention:$identifier_A_identifier\t$identifier_B_naming_convention:$identifier_B_identifier\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t-\t\r\n";
    
        }
         
         
         
        $response = new Response();
    
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . 'psi_mitab.tab');
    
        $response->setContent($psi_mitab);
    
        return $response;
    }
    

    /**
     * Search Home
     *
     * @Route("/psi_mixml/{search_term}", name="psi_mixml", options={"expose": true}))
     * @Method({"GET", "POST"})
     */
    public function psi_mixmlAction($search_term)
    {
        $psi_mixml = '
	    <xs:schema xmlns="net:sf:psidev:mi" xmlns:xs="http://www.w3.org/2001/XMLSchema" targetNamespace="net:sf:psidev:mi" elementFormDefault="qualified" attributeFormDefault="unqualified">
	    <!-- Root element -->
	    <xs:element name="entrySet">
	    <xs:annotation>
	    <xs:documentation>Root element of the Molecular Interaction Format</xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:sequence>
	    <xs:element name="entry" maxOccurs="unbounded">
	    <xs:annotation>
	    <xs:documentation>
	    Describes one or more interactions as a self-contained unit. Multiple entries from different files can be concatenated into a single entrySet.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:sequence>
	    <xs:element name="source" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Desciption of the source of the entry, usually an organisation
	    </xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:sequence>
	    <xs:element name="names" type="namesType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Name(s) of the data source, for example the organisation name.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="bibref" type="bibrefType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Bibliographic reference for the data source. Example: A paper which describes all interactions of the entry.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="xref" type="xrefType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Cross reference for the data source. Example: Entry in a database of databases.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="attributeList" type="attributeListType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>Further description of the source.</xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    <xs:attribute name="release" use="optional">
	    <xs:simpleType>
	    <xs:restriction base="xs:string">
	    <xs:minLength value="1"/>
	    </xs:restriction>
	    </xs:simpleType>
	    </xs:attribute>
	    <xs:attribute name="releaseDate" type="xs:date" use="optional"/>
	    </xs:complexType>
	    </xs:element>
	    <xs:element name="availabilityList" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Data availability statements, for example copyrights
	    </xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:sequence>
	    <xs:element name="availability" type="availabilityType" minOccurs="0" maxOccurs="unbounded">
	    <xs:annotation>
	    <xs:documentation>
	    Describes data availability, e.g. through a copyright statement. If no availability is given, the data is assumed to be freely available.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    </xs:element>
	    <xs:element name="experimentList" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    All experiments in which the interactions of this entry have been determined
	    </xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:sequence>
	    <xs:element name="experimentDescription" type="experimentType" minOccurs="0" maxOccurs="unbounded">
	    <xs:annotation>
	    <xs:documentation>
	    Describes one set of experimental parameters, usually associated with a single publication.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    </xs:element>
	    <xs:element name="interactorList" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>List of all interactors occurring in the entry</xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:sequence>
	    <xs:element name="interactor" type="interactorElementType" minOccurs="0" maxOccurs="unbounded">
	    <xs:annotation>
	    <xs:documentation>
	    A molecule object in its native state, as described in databases.
	    </xs:documentation>
	    <xs:documentation>
	    Usage: A protein interactor must contain an xref to UniProt and NCBI-GI where possible.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    </xs:element>
	    <xs:element name="interactionList">
	    <xs:annotation>
	    <xs:documentation>List of interactions</xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:sequence>
	    <xs:element name="interaction" maxOccurs="unbounded">
	    <xs:annotation>
	    <xs:documentation>A set of molecules interacting.</xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:complexContent>
	    <xs:extension base="interactionElementType"/>
	    </xs:complexContent>
	    </xs:complexType>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    </xs:element>
	    <xs:element name="attributeList" type="attributeListType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Semi-structured additional description of the data contained in the entry.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    </xs:element>
	    </xs:sequence>
	    <xs:attribute name="level" type="xs:int" use="required" fixed="2">
	    <xs:annotation>
	    <xs:documentation>PSI MI level</xs:documentation>
	    </xs:annotation>
	    </xs:attribute>
	    <xs:attribute name="version" type="xs:int" use="required" fixed="5">
	    <xs:annotation>
	    <xs:documentation>PSI MI version within given level</xs:documentation>
	    </xs:annotation>
	    </xs:attribute>
	    <xs:attribute name="minorVersion" type="xs:int" use="optional" fixed="3"/>
	    </xs:complexType>
	    </xs:element>
	    <!-- aministrative elements -->
	    <xs:complexType name="labelType">
	    <xs:annotation>
	    <xs:documentation>
	    A short alphanumeric label identifying an object. Not necessarily unique.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:simpleContent>
	    <xs:extension base="xs:string"/>
	    </xs:simpleContent>
	    </xs:complexType>
	    <xs:complexType name="fullNameType">
	    <xs:annotation>
	    <xs:documentation>Full, descriptive object name.</xs:documentation>
	    </xs:annotation>
	    <xs:simpleContent>
	    <xs:extension base="xs:string"/>
	    </xs:simpleContent>
	    </xs:complexType>
	    <xs:complexType name="experimentRefListType">
	    <xs:annotation>
	    <xs:documentation>
	    Refers to a list of experiments within the same entry.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:sequence>
	    <xs:element name="experimentRef" type="xs:int" maxOccurs="unbounded">
	    <xs:annotation>
	    <xs:documentation>
	    References an experiment already present in this entry.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    <xs:complexType name="xrefType">
	    <xs:annotation>
	    <xs:documentation>
	    Crossreference to an external database. Crossreferences to literature databases, e.g. PubMed, should not be put into this structure, but into the bibRef element where possible.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:sequence>
	    <xs:element name="primaryRef" type="dbReferenceType">
	    <xs:annotation>
	    <xs:documentation>Primary reference to an external database.</xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="secondaryRef" type="dbReferenceType" minOccurs="0" maxOccurs="unbounded">
	    <xs:annotation>
	    <xs:documentation>Further external objects describing the object.</xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    <xs:complexType name="dbReferenceType">
	    <xs:annotation>
	    <xs:documentation>Refers to a unique object in an external database.</xs:documentation>
	    </xs:annotation>
	    <xs:sequence minOccurs="0">
	    <xs:element name="attributeList" type="attributeListType"/>
	    </xs:sequence>
	    <xs:attribute name="db" use="required">
	    <xs:annotation>
	    <xs:documentation>
	    Name of the external database. Taken from the controlled vocabulary of databases.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:simpleType>
	    <xs:restriction base="xs:string">
	    <xs:minLength value="1"/>
	    </xs:restriction>
	    </xs:simpleType>
	    </xs:attribute>
	    <xs:attribute name="dbAc" use="optional">
	    <xs:annotation>
	    <xs:documentation>
	    Accession number of the database in the database CV. This element is controlled by the PSI-MI controlled vocabulary "database citation", root term id MI:0444.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:simpleType>
	    <xs:restriction base="xs:string">
	    <xs:minLength value="1"/>
	    </xs:restriction>
	    </xs:simpleType>
	    </xs:attribute>
	    <xs:attribute name="id" use="required">
	    <xs:annotation>
	    <xs:documentation>
	    Primary identifier of the object in the external database, e.g. UniProt accession number.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:simpleType>
	    <xs:restriction base="xs:string">
	    <xs:minLength value="1"/>
	    </xs:restriction>
	    </xs:simpleType>
	    </xs:attribute>
	    <xs:attribute name="secondary" use="optional">
	    <xs:annotation>
	    <xs:documentation>
	    Secondary identifier of the object in the external database, e.g. UniProt ID.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:simpleType>
	    <xs:restriction base="xs:string">
	    <xs:minLength value="1"/>
	    </xs:restriction>
	    </xs:simpleType>
	    </xs:attribute>
	    <xs:attribute name="version" use="optional">
	    <xs:annotation>
	    <xs:documentation>
	    The version number of the object in the external database.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:simpleType>
	    <xs:restriction base="xs:string">
	    <xs:minLength value="1"/>
	    </xs:restriction>
	    </xs:simpleType>
	    </xs:attribute>
	    <xs:attribute name="refType" use="optional">
	    <xs:annotation>
	    <xs:documentation>
	    Reference type, e.g. "identity" if this reference referes to an identical object in the external database, or "see-also" for additional information. Controlled by CV.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:simpleType>
	    <xs:restriction base="xs:string">
	    <xs:minLength value="1"/>
	    </xs:restriction>
	    </xs:simpleType>
	    </xs:attribute>
	    <xs:attribute name="refTypeAc" use="optional">
	    <xs:annotation>
	    <xs:documentation>
	    Reference type accession number from the CV of reference types. This element is controlled by the PSI-MI controlled vocabulary "xref type", root term id MI:0353.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:simpleType>
	    <xs:restriction base="xs:string">
	    <xs:minLength value="1"/>
	    </xs:restriction>
	    </xs:simpleType>
	    </xs:attribute>
	    </xs:complexType>
	    <xs:complexType name="bibrefType">
	    <xs:annotation>
	    <xs:documentation>Bibliographic reference.</xs:documentation>
	    </xs:annotation>
	    <xs:choice>
	    <xs:element name="xref" type="xrefType">
	    <xs:annotation>
	    <xs:documentation>
	    Bibliographic reference in external database, usually PubMed.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="attributeList" type="attributeListType">
	    <xs:annotation>
	    <xs:documentation>
	    Alternative description of bibliographic reference if no external database entry is available.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:choice>
	    </xs:complexType>
	    <xs:complexType name="attributeListType">
	    <xs:annotation>
	    <xs:documentation>
	    A list of additional attributes. Open tag-value list to allow the inclusion of additional data.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:sequence>
	    <xs:element name="attribute" maxOccurs="unbounded">
	    <xs:complexType>
	    <xs:simpleContent>
	    <xs:extension base="xs:string">
	    <xs:attribute name="name" use="required">
	    <xs:annotation>
	    <xs:documentation>The name of the attribute.</xs:documentation>
	    </xs:annotation>
	    <xs:simpleType>
	    <xs:restriction base="xs:string">
	    <xs:minLength value="1"/>
	    </xs:restriction>
	    </xs:simpleType>
	    </xs:attribute>
	    <xs:attribute name="nameAc" use="optional">
	    <xs:annotation>
	    <xs:documentation>
	    Enables control of the attribute type through reference to an external controlled vocabulary. Root element in the PSI MI CV is MI:0590.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:simpleType>
	    <xs:restriction base="xs:string">
	    <xs:minLength value="1"/>
	    </xs:restriction>
	    </xs:simpleType>
	    </xs:attribute>
	    </xs:extension>
	    </xs:simpleContent>
	    </xs:complexType>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    <xs:complexType name="confidenceType">
	    <xs:annotation>
	    <xs:documentation>A confidence value.</xs:documentation>
	    </xs:annotation>
	    <xs:sequence>
	    <xs:element name="unit" type="openCvType"/>
	    <xs:element name="value">
	    <xs:simpleType>
	    <xs:restriction base="xs:string">
	    <xs:minLength value="1"/>
	    </xs:restriction>
	    </xs:simpleType>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    <xs:complexType name="bioSourceType">
	    <xs:annotation>
	    <xs:documentation>
	    Describes the biological source of an object, in simple form only the NCBI taxid.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:sequence>
	    <xs:element name="names" type="namesType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    The names of the organism. The short label should be a common name if it exists. The full name should be the full name of the species (i.e. genus species).
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="cellType" type="openCvType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Description of the cell type. Currently no species-independent controlled vocabulary for cell types is available, therefore the choice of reference database(s) is open to the data provider.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="compartment" type="openCvType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    The subcellular compartment of the object. It is strongly recommended to refer to the Gene Ontology cellular component in this element.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="tissue" type="openCvType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Description of the source tissue. Currently no species-independent controlled vocabulary for tissues is available, therefore the choice of reference database(s) is open to the data provider.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    <xs:attribute name="ncbiTaxId" type="xs:int" use="required"/>
	    </xs:complexType>
	    <xs:complexType name="confidenceListType">
	    <xs:annotation>
	    <xs:documentation>A list of confidence values.</xs:documentation>
	    </xs:annotation>
	    <xs:sequence>
	    <xs:element name="confidence" maxOccurs="unbounded">
	    <xs:complexType>
	    <xs:complexContent>
	    <xs:extension base="confidenceType">
	    <xs:sequence minOccurs="0">
	    <xs:element name="experimentRefList" type="experimentRefListType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Each experiment might assign a different confidence to this object. If no experimentRef is given, it is assumed this confidence refers to all experiments linked to the object.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    </xs:extension>
	    </xs:complexContent>
	    </xs:complexType>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    <xs:complexType name="cvType">
	    <xs:annotation>
	    <xs:documentation>Reference to an external controlled vocabulary.</xs:documentation>
	    </xs:annotation>
	    <xs:sequence>
	    <xs:element name="names" type="namesType">
	    <xs:annotation>
	    <xs:documentation>Name of the controlled vocabulary term.</xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="xref" type="xrefType">
	    <xs:annotation>
	    <xs:documentation>
	    Source of the controlled vocabulary term. E.g. the name of the CV and the term ID.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    <xs:complexType name="openCvType">
	    <xs:annotation>
	    <xs:documentation>
	    Allows to reference an external controlled vocabulary, or to directly include a value if no suitable external definition is available.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:sequence>
	    <xs:element name="names" type="namesType">
	    <xs:annotation>
	    <xs:documentation>
	    This contains the controlled vocabulary terms, as a short and optionally as a long form.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="xref" type="xrefType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Refers to the term of the controlled vocabulary in an external database.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="attributeList" type="attributeListType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    If no suitable external controlled vocabulary is available, this attributeList can be used to describe the term. Example: Attribute name: Mouse atlas tissue name; attribute value: spinal cord, day 30.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    <xs:complexType name="namesType">
	    <xs:annotation>
	    <xs:documentation>Names for an object.</xs:documentation>
	    </xs:annotation>
	    <xs:sequence>
	    <xs:element name="shortLabel" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    A short string, suitable to remember the object. Can be e.g. a gene name, the first author of a paper, etc.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:simpleType>
	    <xs:restriction base="xs:string">
	    <xs:minLength value="1"/>
	    </xs:restriction>
	    </xs:simpleType>
	    </xs:element>
	    <xs:element name="fullName" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    A full, detailed name or description of the object. Can be e.g. the full title of a publication, or the scientific name of a species.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:simpleType>
	    <xs:restriction base="xs:string">
	    <xs:minLength value="1"/>
	    </xs:restriction>
	    </xs:simpleType>
	    </xs:element>
	    <xs:element name="alias" minOccurs="0" maxOccurs="unbounded">
	    <xs:complexType>
	    <xs:simpleContent>
	    <xs:extension base="xs:string">
	    <xs:attribute name="typeAc" use="optional">
	    <xs:simpleType>
	    <xs:restriction base="xs:string">
	    <xs:minLength value="1"/>
	    </xs:restriction>
	    </xs:simpleType>
	    </xs:attribute>
	    <xs:attribute name="type" use="optional">
	    <xs:simpleType>
	    <xs:restriction base="xs:string">
	    <xs:minLength value="1"/>
	    </xs:restriction>
	    </xs:simpleType>
	    </xs:attribute>
	    </xs:extension>
	    </xs:simpleContent>
	    </xs:complexType>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    <xs:complexType name="parameterType">
	    <xs:annotation>
	    <xs:documentation>A numeric parameter, e.g. for a kinetic value</xs:documentation>
	    </xs:annotation>
	    <xs:attribute name="term" use="required">
	    <xs:annotation>
	    <xs:documentation>
	    The kind of parameter, e.g. "dissociation constant".
	    </xs:documentation>
	    </xs:annotation>
	    <xs:simpleType>
	    <xs:restriction base="xs:string">
	    <xs:minLength value="1"/>
	    </xs:restriction>
	    </xs:simpleType>
	    </xs:attribute>
	    <xs:attribute name="termAc" use="optional">
	    <xs:annotation>
	    <xs:documentation>
	    Accession number of the term in the associated controlled vocabulary.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:simpleType>
	    <xs:restriction base="xs:string">
	    <xs:minLength value="1"/>
	    </xs:restriction>
	    </xs:simpleType>
	    </xs:attribute>
	    <xs:attribute name="unit" use="optional">
	    <xs:annotation>
	    <xs:documentation>The unit of the term, e.g. "kiloDalton".</xs:documentation>
	    </xs:annotation>
	    <xs:simpleType>
	    <xs:restriction base="xs:string">
	    <xs:minLength value="1"/>
	    </xs:restriction>
	    </xs:simpleType>
	    </xs:attribute>
	    <xs:attribute name="unitAc" use="optional">
	    <xs:annotation>
	    <xs:documentation>
	    Accession number of the unit in the associated controlled vocabulary.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:simpleType>
	    <xs:restriction base="xs:string">
	    <xs:minLength value="1"/>
	    </xs:restriction>
	    </xs:simpleType>
	    </xs:attribute>
	    <xs:attribute name="base" type="xs:short" use="optional" default="10">
	    <xs:annotation>
	    <xs:documentation>Base of the parameter expression. Defaults to 10.</xs:documentation>
	    </xs:annotation>
	    </xs:attribute>
	    <xs:attribute name="exponent" type="xs:short" use="optional" default="0">
	    <xs:annotation>
	    <xs:documentation>Exponent of the value.</xs:documentation>
	    </xs:annotation>
	    </xs:attribute>
	    <xs:attribute name="factor" type="xs:decimal" use="required">
	    <xs:annotation>
	    <xs:documentation>The "main" value of the parameter.</xs:documentation>
	    </xs:annotation>
	    </xs:attribute>
	    </xs:complexType>
	    <!-- Feature description -->
	    <xs:complexType name="featureElementType">
	    <xs:annotation>
	    <xs:documentation>A feature, e.g. domain, on a sequence.</xs:documentation>
	    </xs:annotation>
	    <xs:sequence>
	    <xs:element name="names" type="namesType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>Names for the feature, e.g. SH3 domain.</xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="xref" type="xrefType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Reference to an external feature description, for example InterPro entry.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="featureType" type="cvType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Description and classification of the feature. This element is controlled by the PSI-MI controlled vocabulary "featureType", root term id MI:0116.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="featureDetectionMethod" type="cvType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Experimental method used to identify the feature. A setting here overrides the global setting given in the experimentDescription. External controlled vocabulary.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="experimentRefList" type="experimentRefListType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    If no experimentRef is given, it is assumed this refers to all experiments linked to the interaction.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="featureRangeList">
	    <xs:complexType>
	    <xs:sequence>
	    <xs:element name="featureRange" type="baseLocationType" maxOccurs="unbounded">
	    <xs:annotation>
	    <xs:documentation>
	    Location of the feature on the sequence of the interactor. One feature may have more than one featureRange, used e.g. for features which involve sequence positions close in the folded, three-dimensional state of a protein, but non-continuous along the sequence.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    </xs:element>
	    <xs:element name="attributeList" type="attributeListType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Semi-structured additional description of the data contained in the entry.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    <xs:attribute name="id" type="xs:int" use="required"/>
	    </xs:complexType>
	    <xs:complexType name="intervalType">
	    <xs:annotation>
	    <xs:documentation>A interval on a sequence.</xs:documentation>
	    </xs:annotation>
	    <xs:attribute name="begin" type="xs:unsignedLong" use="required"/>
	    <xs:attribute name="end" type="xs:unsignedLong" use="required"/>
	    </xs:complexType>
	    <xs:complexType name="baseLocationType">
	    <xs:annotation>
	    <xs:documentation>
	    A location on a sequence. Both begin and end can be a defined position, a fuzzy position, or undetermined.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:sequence>
	    <xs:sequence>
	    <xs:element name="startStatus" type="cvType">
	    <xs:annotation>
	    <xs:documentation>
	    Attribute of the start positions, e.g. "certain" or "n-terminal"
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:choice minOccurs="0">
	    <xs:element name="begin" type="positionType">
	    <xs:annotation>
	    <xs:documentation>
	    The integer position gives the begin position of the feature. The first base or amino acid is position 1. In combination with the numeric value, the attribute status allows to express fuzzy positions, e.g. less than 4.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="beginInterval" type="intervalType">
	    <xs:annotation>
	    <xs:documentation>
	    The begin position may be varying or unclear, but localisable to a certain range. Usually written as e.g. 3..5.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:choice>
	    </xs:sequence>
	    <xs:sequence>
	    <xs:element name="endStatus" type="cvType">
	    <xs:annotation>
	    <xs:documentation>
	    Attribute of the end positions, e.g. "certain" or "c-terminal"
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:choice minOccurs="0">
	    <xs:element name="end" type="positionType">
	    <xs:annotation>
	    <xs:documentation>
	    The integer position gives the end position of the feature. The first base or amino acid is position 1. In combination with the numeric value, the attribute status allows to express fuzzy positions, e.g. more than 400.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="endInterval" type="intervalType">
	    <xs:annotation>
	    <xs:documentation>
	    The end position may be varying or unclear, but localisable to a certain range. Usually written as e.g. 3..5.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:choice>
	    </xs:sequence>
	    <xs:element name="isLink" type="xs:boolean" default="false" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    True if the described feature is a linking feature connecting two amino acids rather than extending along the sequence. begin references the first amino acid, end the second. Standard example is a disulfide bridge. Does not reference another feature, therefore is only suitable for linking features on the same amino acid chain.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    <xs:complexType name="positionType">
	    <xs:attribute name="position" type="xs:unsignedLong" use="required"/>
	    </xs:complexType>
	    <!-- Top level types -->
	    <xs:complexType name="availabilityType">
	    <xs:annotation>
	    <xs:documentation>
	    A text describing the availability of data, e.g. a copyright statement.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:simpleContent>
	    <xs:extension base="xs:string">
	    <xs:attribute name="id" type="xs:int" use="required"/>
	    </xs:extension>
	    </xs:simpleContent>
	    </xs:complexType>
	    <xs:complexType name="experimentType">
	    <xs:annotation>
	    <xs:documentation>Describes one set of experimental parameters.</xs:documentation>
	    </xs:annotation>
	    <xs:sequence>
	    <xs:element name="names" type="namesType" minOccurs="0"/>
	    <xs:element name="bibref" type="bibrefType">
	    <xs:annotation>
	    <xs:documentation>Publication describing the experiment.</xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="xref" type="xrefType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Refers to external database description of the experiment.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="hostOrganismList" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    The host organism(s) in which the experiment has been performed.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:sequence>
	    <xs:element name="hostOrganism" maxOccurs="unbounded">
	    <xs:complexType>
	    <xs:complexContent>
	    <xs:extension base="bioSourceType"/>
	    </xs:complexContent>
	    </xs:complexType>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    </xs:element>
	    <xs:element name="interactionDetectionMethod" type="cvType">
	    <xs:annotation>
	    <xs:documentation>
	    Experimental method to determine the interaction. This element is controlled by the PSI-MI controlled vocabulary "interaction detection method", root term id MI:0001.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="participantIdentificationMethod" type="cvType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Experimental method to determine the interactors involved in the interaction. This element is controlled by the PSI-MI controlled vocabulary "participant identification method", root term id MI:0002.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="featureDetectionMethod" type="cvType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Experimental method to determine the features of interactors. If this element is filled it is assumed to apply to all features described in the experiment. But can be overridden by the featureDetectionMethod given in the individual feature. This element is controlled by the PSI-MI controlled vocabulary "feature detection method", root term id MI:0003.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="confidenceList" type="confidenceListType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Confidence in this experiment. Usually a statistical measure.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="attributeList" type="attributeListType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Semi-structured additional description of the experiment.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    <xs:attribute name="id" type="xs:int" use="required">
	    <xs:annotation>
	    <xs:documentation>
	    All major objects have a numerical id which must be unique to that object within an entry. The object may be repeated, though, e.g. in the denormalised representation.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:attribute>
	    </xs:complexType>
	    <xs:complexType name="interactionElementType">
	    <xs:annotation>
	    <xs:documentation>A molecular interaction.</xs:documentation>
	    </xs:annotation>
	    <xs:sequence>
	    <xs:element name="names" type="namesType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>Name(s) of the interaction.</xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="xref" type="xrefType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>Interaction database ID</xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:choice minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Either refer to an already defined availability statement in this entry or insert description.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:element name="availabilityRef" type="xs:int">
	    <xs:annotation>
	    <xs:documentation>
	    References an availability statement already present in this entry.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="availability" type="availabilityType">
	    <xs:annotation>
	    <xs:documentation>
	    Describes the availability of the interaction data. If no availability is given, the data is assumed to be freely available.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:choice>
	    <xs:element name="experimentList">
	    <xs:annotation>
	    <xs:documentation>
	    List of experiments in which this interaction has been determined.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:choice maxOccurs="unbounded">
	    <xs:annotation>
	    <xs:documentation>
	    Either refer to an already defined experiment in this entry or insert description.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:element name="experimentRef" type="xs:int">
	    <xs:annotation>
	    <xs:documentation>
	    References an experiment already present in this entry.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="experimentDescription" type="experimentType">
	    <xs:annotation>
	    <xs:documentation>
	    An experiment in which this interaction has been determined.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:choice>
	    </xs:complexType>
	    </xs:element>
	    <xs:element name="participantList">
	    <xs:annotation>
	    <xs:documentation>
	    A list of molecules participating in this interaction. An interaction has one (intramolecular), two (binary), or more (n-ary, complexes) participants.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:sequence>
	    <xs:element name="participant" type="participantType" maxOccurs="unbounded"/>
	    </xs:sequence>
	    </xs:complexType>
	    </xs:element>
	    <xs:element name="inferredInteractionList" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Describes inferred interactions, usually combining data from more than one experiment. Examples: 1: Show the topology of binary interactions within a complex. 2: Interaction inferred from multiple experiments which on their own would not support the interaction. Example: A-B in experiment 1, B-C- in experiment 2, A-C is the inferred interaction.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:sequence>
	    <xs:element name="inferredInteraction" maxOccurs="unbounded">
	    <xs:complexType>
	    <xs:sequence>
	    <xs:element name="participant" minOccurs="2" maxOccurs="unbounded">
	    <xs:annotation>
	    <xs:documentation>Participant of the inferred interaction.</xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:choice>
	    <xs:element name="participantRef" type="xs:int"/>
	    <xs:element name="participantFeatureRef" type="xs:int"/>
	    </xs:choice>
	    </xs:complexType>
	    </xs:element>
	    <xs:element name="experimentRefList" type="experimentRefListType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    If no experimentRef is given, it is assumed this refers to all experiments linked to the interaction.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    </xs:element>
	    <xs:element name="interactionType" type="cvType" minOccurs="0" maxOccurs="unbounded">
	    <xs:annotation>
	    <xs:documentation>
	    External controlled vocabulary characterising the interaction type, for example "physical interaction".
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="modelled" type="xs:boolean" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    If true, this element describes an interaction in a species of interest, e.g. human, but has actually been investigated in another organism, e.g. mouse. The transfer will usually be based on a homology statement made by the data producer. If this optional element is missing, it is assumed to be set to false.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="intraMolecular" type="xs:boolean" default="false" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    If true, this interaction is an intramolecular interaction, e.g. an autophosphorylation. If missing, this element is assumed to be false.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="negative" type="xs:boolean" default="false" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    If true, this interaction has been shown NOT to occur under the described experimental conditions. Default false. If this optional element is missing, it is assumed to be set to false.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="confidenceList" type="confidenceListType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Confidence in this interaction. Usually a statistical measure.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="parameterList" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Lists parameters which are relevant for the Interaction, e.g. kinetics.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:sequence>
	    <xs:element name="parameter" maxOccurs="unbounded">
	    <xs:complexType>
	    <xs:complexContent>
	    <xs:extension base="parameterType">
	    <xs:sequence>
	    <xs:element name="experimentRef" type="xs:int">
	    <xs:annotation>
	    <xs:documentation>
	    Reference to the experiment in which this parameter has been determined. If not given, it is assumed that this is valid for all experiments attached to the interaction.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    <xs:attribute name="uncertainty" type="xs:decimal" use="optional"/>
	    </xs:extension>
	    </xs:complexContent>
	    </xs:complexType>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    </xs:element>
	    <xs:element name="attributeList" type="attributeListType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Semi-structured additional description of the data contained in the entry.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    <xs:attribute name="imexId" type="xs:string" use="optional">
	    <xs:annotation>
	    <xs:documentation>
	    Interaction identifier assigned by the IMEx consortium. Will be unique for an interaction determined in one publication. Details defined in the IMEx documents.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:attribute>
	    <xs:attribute name="id" type="xs:int" use="required">
	    <xs:annotation>
	    <xs:documentation>
	    All major objects have a numerical id which is unique to that object within a PSI MI file. The object may be repeated, though, e.g. in the denormalised representation.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:attribute>
	    </xs:complexType>
	    <!-- interactors and participants -->
	    <xs:complexType name="interactorElementType">
	    <xs:annotation>
	    <xs:documentation>Describes a molecular interactor.</xs:documentation>
	    </xs:annotation>
	    <xs:sequence>
	    <xs:element name="names" type="namesType">
	    <xs:annotation>
	    <xs:documentation>
	    Name(s). The short label is typically a short name that could appear as a label on a diagram.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="xref" type="xrefType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    An interactor should have an xref whenever possible. If the interactor is not available in external databases, it must be characterised within this object e.g. by its sequence.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="interactorType" type="cvType">
	    <xs:annotation>
	    <xs:documentation>
	    The molecule type of the participant, e.g. protein. This element is controlled by the PSI-MI controlled vocabulary "interactorType", root term id MI:0313.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="organism" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>The normal source organism of the interactor.</xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:complexContent>
	    <xs:extension base="bioSourceType"/>
	    </xs:complexContent>
	    </xs:complexType>
	    </xs:element>
	    <xs:element name="sequence" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>Sequence in uppercase</xs:documentation>
	    <xs:documentation>Usage:</xs:documentation>
	    </xs:annotation>
	    <xs:simpleType>
	    <xs:restriction base="xs:string">
	    <xs:minLength value="1"/>
	    </xs:restriction>
	    </xs:simpleType>
	    </xs:element>
	    <xs:element name="attributeList" type="attributeListType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Allows semi-structured additional annotation of the interactor.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    <xs:attribute name="id" type="xs:int" use="required">
	    <xs:annotation>
	    <xs:documentation>
	    All major objects have a numerical id which is unique to that object within a PSI MI file. The object may be repeated, though, e.g. in the denormalised representation.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:attribute>
	    </xs:complexType>
	    <xs:complexType name="participantType">
	    <xs:annotation>
	    <xs:documentation>A molecule participating in an interaction.</xs:documentation>
	    </xs:annotation>
	    <xs:sequence>
	    <xs:element name="names" type="namesType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    This contains the name(s) for the participant given by the authors of a publication.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="xref" type="xrefType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Contains the xref(s) for the participant given by the authors of a publication.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:choice>
	    <xs:annotation>
	    <xs:documentation>
	    Description of the Interactor. Refers to an already defined interactor in this entry, fully describes an interactor, or references another interaction defined in this entry, to allow the hierarchical building up of complexes from subunits.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:element name="interactorRef" type="xs:int">
	    <xs:annotation>
	    <xs:documentation>
	    References an interactor described in the interactorList of the entry
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="interactor" type="interactorElementType">
	    <xs:annotation>
	    <xs:documentation>Fully describes an interactor</xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="interactionRef" type="xs:int">
	    <xs:annotation>
	    <xs:documentation>
	    References an interaction described in this entry. Used for the hierarchical buildup of complexes.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:choice>
	    <xs:element name="participantIdentificationMethodList" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    The method(s) by which this participant has been determined. If this element is present, its value supersedes experimentDescription/ participantIdentificationMethod.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:sequence>
	    <xs:element name="participantIdentificationMethod" maxOccurs="unbounded">
	    <xs:annotation>
	    <xs:documentation>
	    Experimental method to determine the interactors involved in the interaction. This element is controlled by the PSI-MI controlled vocabulary "participant identification method", root term id MI:0002.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:complexContent>
	    <xs:extension base="cvType">
	    <xs:sequence minOccurs="0">
	    <xs:element name="experimentRefList" type="experimentRefListType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    If no experimentRef is given, it is assumed this refers to all experiments linked to the interaction.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    </xs:extension>
	    </xs:complexContent>
	    </xs:complexType>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    </xs:element>
	    <xs:element name="biologicalRole" type="cvType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    The role of the participant in the interaction. This describes the biological role, e.g. enzyme or enzyme target. The experimental role of the participant, e.g. bait, is shown in experimentalForm. This element is controlled by the PSI-MI controlled vocabulary "biologicalRole", root term id MI:0500.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="experimentalRoleList" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    The role(s) of the participant in the interaction, e.g. bait.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:sequence>
	    <xs:element name="experimentalRole" maxOccurs="unbounded">
	    <xs:annotation>
	    <xs:documentation>
	    This element is controlled by the PSI-MI controlled vocabulary "experimentalRole", root term id MI:0495.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:complexContent>
	    <xs:extension base="cvType">
	    <xs:sequence minOccurs="0">
	    <xs:element name="experimentRefList" type="experimentRefListType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    If no experimentRef is given, it is assumed this refers to all experiments linked to the interaction.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    </xs:extension>
	    </xs:complexContent>
	    </xs:complexType>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    </xs:element>
	    <xs:element name="experimentalPreparationList" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Terms describing the experimental sample preparation.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:sequence>
	    <xs:element name="experimentalPreparation" maxOccurs="unbounded">
	    <xs:annotation>
	    <xs:documentation>
	    This element is controlled by the PSI-MI controlled vocabulary "experimentalPreparation", root term id MI:0346.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:complexContent>
	    <xs:extension base="cvType">
	    <xs:sequence minOccurs="0">
	    <xs:element name="experimentRefList" type="experimentRefListType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    If no experimentRef is given, it is assumed this refers to all experiments linked to the interaction.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    </xs:extension>
	    </xs:complexContent>
	    </xs:complexType>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    </xs:element>
	    <xs:element name="experimentalInteractorList" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Describes molecules which have been used in specific experiments if these molecules are different from the one listed as interactors. Example: The author of a paper makes a statement about human proteins, but has really worked with mouse proteins. In this case the human protein would be the main interactor, while the experimentalForm would be the mouse protein listed in this element. Optionally this can refer to the experiment(s) in which this form has been used.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:sequence>
	    <xs:element name="experimentalInteractor" maxOccurs="unbounded">
	    <xs:complexType>
	    <xs:sequence>
	    <xs:choice>
	    <xs:annotation>
	    <xs:documentation>
	    Either refer to an already defined protein interactor in this entry or insert description.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:element name="interactorRef" type="xs:int">
	    <xs:annotation>
	    <xs:documentation>
	    References an interactor described in the interactorList of the entry
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="interactor" type="interactorElementType">
	    <xs:annotation>
	    <xs:documentation>Fully describes an interactor</xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:choice>
	    <xs:element name="experimentRefList" type="experimentRefListType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    If no experimentRef is given, it is assumed this refers to all experiments linked to the interaction.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    </xs:element>
	    <xs:element name="featureList" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Sequence features relevant for the interaction, for example binding domains, and experimental modifications, e.g. protein tags.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:sequence>
	    <xs:element name="feature" type="featureElementType" maxOccurs="unbounded"/>
	    </xs:sequence>
	    </xs:complexType>
	    </xs:element>
	    <xs:element name="hostOrganismList" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    The host organism(s) in which the protein has been expressed. If not given, it is assumed to be the native species of the protein.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:sequence>
	    <xs:element name="hostOrganism" minOccurs="0" maxOccurs="unbounded">
	    <xs:complexType>
	    <xs:complexContent>
	    <xs:extension base="bioSourceType">
	    <xs:sequence minOccurs="0">
	    <xs:element name="experimentRefList" type="experimentRefListType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    If no experimentRef is given, it is assumed this refers to all experiments linked to the interaction.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    </xs:extension>
	    </xs:complexContent>
	    </xs:complexType>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    </xs:element>
	    <xs:element name="confidenceList" type="confidenceListType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>Confidence in participant detection.</xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    <xs:element name="parameterList" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Lists parameters which are relevant for the Interaction, e.g. kinetics.
	    </xs:documentation>
	    </xs:annotation>
	    <xs:complexType>
	    <xs:sequence>
	    <xs:element name="parameter" maxOccurs="unbounded">
	    <xs:complexType>
	    <xs:complexContent>
	    <xs:extension base="parameterType">
	    <xs:sequence>
	    <xs:element name="experimentRef" type="xs:int">
	    <xs:annotation>
	    <xs:documentation>
	    Reference to the experiment in which this parameter has been determined. If not given, it is assumed that this is valid for all experiments attached to the interaction.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    <xs:attribute name="uncertainty" type="xs:decimal" use="optional"/>
	    </xs:extension>
	    </xs:complexContent>
	    </xs:complexType>
	    </xs:element>
	    </xs:sequence>
	    </xs:complexType>
	    </xs:element>
	    <xs:element name="attributeList" type="attributeListType" minOccurs="0">
	    <xs:annotation>
	    <xs:documentation>
	    Semi-structured additional description of the data contained in the entry.
	    </xs:documentation>
	    </xs:annotation>
	    </xs:element>
	    </xs:sequence>
	    <xs:attribute name="id" type="xs:int" use="required"/>
	    </xs:complexType>
	    </xs:schema>';

        $response = new Response();

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . 'psi_mixml.xml');

        $response->setContent($psi_mixml);

        return $response;
    }

}

?>