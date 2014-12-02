import csv
import urllib
import urllib.parse
import urllib.request
import json
import sys
import config as cnf

csvFilename = cnf.csvFilename
# Credentials and feature service information
username = cnf.username
password = cnf.password
service = cnf.service
fsURL = cnf.fsURL

class AGOLHandler(object):    
    """
    ArcGIS Online handler class.
      -Generates and keeps tokens
      -template JSON feature objects for point
    """
    
    def __init__(self, username, password, serviceName):
        self.username = username
        self.password = password
        self.serviceName = serviceName
        self.token, self.http, self.expires= self.getToken(username, password)  

    def getToken(self, username, password, exp=60):  # expires in 60minutes
        """Generates a token."""
        referer = "http://www.arcgis.com/"
        query_dict = {'username': username,
                      'password': password,
                      'referer': referer}

        query_string = urllib.parse.urlencode(query_dict)
        query_string = query_string.encode('utf-8')
        url = "https://www.arcgis.com/sharing/rest/generateToken"
        data = urllib.request.urlopen(url + "?f=json", query_string)
        token = json.loads(data.read().decode('utf-8'))

        if "token" not in token:
            print(token['error'])
            sys.exit(1)
        else:
            httpPrefix = "http://www.arcgis.com/sharing/rest"
            if token['ssl'] is True:
                httpPrefix = "https://www.arcgis.com/sharing/rest"
            return token['token'], httpPrefix, token['expires'] 
        
        
    def jsonPoint(self, X, Y, ptTime):
        """Customized JSON point object for ISS schema"""
        return {
            "attributes": {
                "OBJECTID": 1,
                "TextDate": time.strftime('%m/%d/%Y %H:%MZ', time.gmtime(ptTime)),
                "Long": Y,
                "Lat": X
            },
            "geometry": {
                "x": X,
                "y": Y
            }
        }
       


def send_AGOL_Request(URL, query_dict, returnType=False):
    """
    Helper function which takes a URL and a dictionary and sends the request.
    returnType values = 
         False : make sure the geometry was updated properly
         "JSON" : simply return the raw response from the request, it will be parsed by the calling function
         else (number) : a numeric value will be used to ensure that number of features exist in the response JSON
    """
    
    query_string = urllib.parse.urlencode(query_dict).encode('utf-8')

    jsonResponse = urllib.request.urlopen(URL, query_string)
    jsonOuput = json.loads(jsonResponse.read().decode('utf-8'))
   
   
         
    if returnType == "JSON":
        return jsonOuput
    
    if not returnType:
        if "addResults" in jsonOuput:
            try:            
                for item in jsonOuput['addResults']:                    
                    if item['success'] is True:
                        print("request submitted successfully")
            except:
                print("Error: {0}".format(jsonOuput))
                return False
    else:
        if "deleteResults" in jsonOuput:
            try:            
                for item in jsonOuput['deleteResults']:                    
                    if item['success'] is True:
                       print("request submitted successfully")
            except:
                print("Error: {0}".format(jsonOuput))
                return False
            
    #else:  # Check that the proper number of features exist in a layer
    #    if len(jsonOuput['features']) != returnType:
    #        print("FS layer needs seed values")
    #        return False
            
    return True


def fillEmptyGeo(con, fsURL):
    """
    This function queries the service layer end points to ensure there is geometry as the
    script does an update on existing geometry.
    If there are no features, a dummy point is entered.
    """
        
    ptURL = fsURL + "/0/query"
    
    query_dict = {
        "f": "json",
        "where": "1=1",
        "token": con.token
    }

    # Check 1 point exists in the point layer (0), if not, add a value
    if (send_AGOL_Request(ptURL, query_dict, 1)) is False:
        
        ptGeoURL = fsURL + "/0/addFeatures"
        ptData = {
            "features": con.jsonPoint(0, 0, 1000000000),
            "f": "json",
            "token": con.token
        }
        
        if send_AGOL_Request(ptGeoURL, ptData):
            print("Inserted dummy point")
            
    return

def removeGeo(con, fsURL):
    """
    This function queries the service layer end points to ensure there is geometry as the
    script does an update on existing geometry.
    If there are no features, a dummy point is entered.
    """
        
    ptURL = fsURL + "/0/deleteFeatures"
    
    query_dict = {
        "f": "json",
        "where": "1=1",
        "token": con.token
    }

    # Check 1 point exists in the point layer (0), if not, add a value
    if (send_AGOL_Request(ptURL, query_dict, 1)) is False:       
            print("Error deleting features")
    else:
            print("All features from service "+fsURL+"deleted")
    return


def updatePoint(con, ptURL, X, Y, ptTime):
    """Use a URL and X/Y values to update an existing point."""
 
    try:
        # Always updates point #1.
        submitData = {
            "features": con.jsonPoint(X, Y, ptTime),
            "f": "json",
            "token": con.token
        }
        
        jUpdate = send_AGOL_Request(ptURL, submitData)          
  
    except:
        print("couldn't update point")

    return



               
def logError (err):
   with open("foutieve adressen.txt", "a") as f:
            f.write(str(err))  
                    
def addPoint(con, ptURL, jSON):
    """Use a URL and X/Y values to update an existing point."""
 
    try:
        # Always updates point #1.
        submitData = {
            "features": jSON,
            "f": "json",
            "token": con.token
        }
        
        jUpdate = send_AGOL_Request(ptURL, submitData)          
        if (jUpdate == False):
            print("Error adding point: "+jSON)
            logError(jSON)
            return -1
    except:
        print("couldn't update point")
        logError(jSON)

    return 1

if __name__ == "__main__": 

    # Initialize the AGOLHandler for token and feature service JSON templates
    con = AGOLHandler(username, password, service)
    
    try:         
        # Check the Feature Service for the required layer and they have at least 1 point
        # This call can be removed if you're certain the layer exists with a point
        print ("Removing all features from ",fsURL)
        removeGeo(con, fsURL) 

        cnt = 0;
        with open(csvFilename) as csvfile: #, 'rb'
            reader = csv.reader(csvfile, delimiter=';', quotechar='|')
            for row in reader:
                #check for lat and long values
                if (str(row[3]).replace('.','',1).replace(',','',1).isdigit() and str(row[4]).replace(',','',1).replace('.','',1).isdigit()):
                    Land = row[0]
                    Straat= row[1]  
                    if str(row[2]).replace('.','',1).replace(',','',1).isdigit():             
                        Nummer= int(row[2])
                    else:
                        Nummer = 0
                    Long = float(row[3].replace(',','.',1))
                    Lat = float(row[4].replace(',','.',1))
              
                   
                    jSON = {
                        "attributes": {
                            "Land":row[0],
                            "Straat":row[1],
                            "Nummer":Nummer,
                            "Long":Long,
                            "Lat": Lat,
                            "Postcode":row[5],
                            "Opdrachtgever":row[6],
                            "Plaats":row[7]
                        },
                        "geometry": {
                            "x": Long,
                            "y": Lat,
                            "spatialReference" : {"wkid" : 4326} 
                        }}                
                    addPoint(con, fsURL + "/0/addFeatures",jSON)
                    print ("{0} Adding feature {1}",cnt+1,row[1])
                           
                    cnt += 1
                
            # Generic exception handling: simple message is printed to the screen so the script continues to run.
        print ("Done publishing ",str(cnt)," features")
            # Additionally, an email or other action could be implemented below.
    except Exception as e:
        print("ERROR caught:  {0}".format(e))